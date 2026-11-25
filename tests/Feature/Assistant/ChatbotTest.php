<?php

namespace Tests\Feature\Assistant;

use App\Assistant\AssistantUnavailable;
use App\Assistant\BusinessContext;
use App\Assistant\Contracts\ChatProvider;
use App\Assistant\Providers\NullChatProvider;
use App\Livewire\Admin\SettingsForm;
use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    /** Covers the pricing rules for guests with B2B price access switched off. */
    protected bool $b2bPricing = false;

    /** Records every call; answers with a canned reply or throws. */
    private function fakeProvider(bool $fail = false): ChatProvider
    {
        $fake = new class($fail) implements ChatProvider
        {
            public array $calls = [];

            public function __construct(private bool $fail) {}

            public function name(): string
            {
                return 'fake';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function model(): ?string
            {
                return 'fake-model';
            }

            public function reply(string $system, array $messages): string
            {
                $this->calls[] = compact('system', 'messages');
                if ($this->fail) {
                    throw new AssistantUnavailable('down');
                }

                return 'Answer #'.count($this->calls).' — see /quote';
            }
        };
        $this->app->instance(ChatProvider::class, $fake);

        return $fake;
    }

    private function enable(array $extra = []): void
    {
        app(Settings::class)->set(['chatbot.enabled' => true] + $extra);
    }

    public function test_chatbot_is_off_by_default(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-testid="chat-widget"', false);
        $this->postJson('/assistant/messages', ['message' => 'Hi'])->assertNotFound();
    }

    public function test_enabled_without_credentials_shows_an_honest_offline_panel(): void
    {
        $this->app->instance(ChatProvider::class, new NullChatProvider);
        $this->enable();

        $this->get('/')->assertSee('data-testid="chat-widget"', false)
            ->assertSee('data-testid="chat-status-offline"', false)
            ->assertSee('Our AI assistant is offline right now.')
            ->assertSee('api.whatsapp.com') // WhatsApp offered as a contact route
            ->assertDontSee('id="chat-input"', false);
        $this->postJson('/assistant/messages', ['message' => 'Hi'])->assertStatus(503)->assertJsonPath('message', fn ($m) => str_contains($m, '/quote'));
    }

    public function test_conversation_round_trip_keeps_history_server_side(): void
    {
        $fake = $this->fakeProvider();
        $this->enable(['chatbot.intro_message' => 'Hello there! Ask me anything about our wallets.']);

        $this->get('/')->assertSee('data-testid="chat-status-online"', false)->assertSee('id="chat-input"', false)
            ->assertSee('Hello there! Ask me anything about our wallets.');

        $this->postJson('/assistant/messages', ['message' => 'What is your MOQ?'])->assertOk()->assertJsonPath('reply', 'Answer #1 — see /quote');
        $this->postJson('/assistant/messages', ['message' => 'And for belts?'])->assertOk();

        $this->assertSame([
            ['role' => 'user', 'content' => 'What is your MOQ?'],
            ['role' => 'assistant', 'content' => 'Answer #1 — see /quote'],
            ['role' => 'user', 'content' => 'And for belts?'],
        ], $fake->calls[1]['messages']);

        // History is restored into the widget on the next page view.
        $this->get('/products')->assertSee('What is your MOQ?');

        $this->postJson('/assistant/reset')->assertOk();
        $this->postJson('/assistant/messages', ['message' => 'Fresh start'])->assertOk();
        $this->assertSame([['role' => 'user', 'content' => 'Fresh start']], $fake->calls[2]['messages']);
    }

    public function test_history_is_capped_and_starts_with_a_visitor_message(): void
    {
        config(['store.assistant.history_messages' => 4]);
        $fake = $this->fakeProvider();
        $this->enable();
        foreach (range(1, 5) as $i) {
            $this->postJson('/assistant/messages', ['message' => "Q{$i}"])->assertOk();
        }
        $last = $fake->calls[4]['messages'];
        $this->assertSame('user', $last[0]['role']);
        $this->assertLessThanOrEqual(5, count($last));
        $this->assertSame('Q5', end($last)['content']);
    }

    public function test_input_is_validated(): void
    {
        $this->fakeProvider();
        $this->enable();
        $this->postJson('/assistant/messages', ['message' => ''])->assertUnprocessable();
        $this->postJson('/assistant/messages', ['message' => str_repeat('a', 1001)])->assertUnprocessable();
        $this->postJson('/assistant/messages', ['message' => "\u{0000}\u{0007}"])->assertUnprocessable();
    }

    public function test_provider_failure_returns_503_and_does_not_store_the_turn(): void
    {
        $this->fakeProvider(fail: true);
        $this->enable();
        $this->postJson('/assistant/messages', ['message' => 'Hello'])->assertStatus(503);
        $this->assertSame([], session('assistant.history', []));
    }

    public function test_messages_are_rate_limited(): void
    {
        config(['store.assistant.rate_per_minute' => 2]);
        $this->fakeProvider();
        $this->enable();
        $this->postJson('/assistant/messages', ['message' => 'a'])->assertOk();
        $this->postJson('/assistant/messages', ['message' => 'b'])->assertOk();
        $this->postJson('/assistant/messages', ['message' => 'c'])->assertStatus(429);
    }

    public function test_an_immediate_resubmission_reuses_the_previous_answer(): void
    {
        $fake = $this->fakeProvider();
        $this->enable();
        $first = $this->postJson('/assistant/messages', ['message' => 'Do you do embossing?'])->assertOk()->json('reply');
        $this->postJson('/assistant/messages', ['message' => '  Do you do embossing?  '])->assertOk()->assertJson(['reply' => $first]);
        $this->assertCount(1, $fake->calls);
        $this->assertCount(2, session('assistant.history'));

        $this->travel(20)->seconds();
        $this->postJson('/assistant/messages', ['message' => 'Do you do embossing?'])->assertOk();
        $this->assertCount(2, $fake->calls);
    }

    public function test_concurrent_requests_from_one_session_are_refused(): void
    {
        $fake = $this->fakeProvider();
        $this->enable();
        // Pin the session ID (as the browser's session cookie does) and hold that session's lock.
        $sessionId = \Illuminate\Support\Str::random(40);
        $this->withCredentials()->withCookie(config('session.cookie'), $sessionId);
        $lock = \Illuminate\Support\Facades\Cache::lock('assistant:'.hash('sha256', $sessionId), 60);
        $this->assertTrue($lock->get());

        $this->postJson('/assistant/messages', ['message' => 'Hello'])->assertStatus(409);
        $this->assertCount(0, $fake->calls);

        $lock->release();
        $this->postJson('/assistant/messages', ['message' => 'Hello'])->assertOk();
        $this->assertCount(1, $fake->calls);
    }

    public function test_requests_require_csrf_outside_tests(): void
    {
        // The route lives in the "web" group, so VerifyCsrfToken applies (tests bypass it by default).
        $route = app('router')->getRoutes()->getByName('assistant.send');
        $this->assertContains('web', $route->gatherMiddleware());
    }

    public function test_business_context_contains_public_facts_only(): void
    {
        $fake = $this->fakeProvider();
        $this->enable(['chatbot.knowledge' => 'We ship samples worldwide within 7 days.']);
        $public = Product::factory()->create(['name' => 'Public Wallet', 'sku' => 'PUB-1', 'base_price_cents' => 1800, 'moq' => 10]);
        PriceTier::create(['product_id' => $public->id, 'min_qty' => 100, 'unit_price_cents' => 1250]);
        Product::factory()->create(['name' => 'Trade Tote', 'public_pricing' => false, 'base_price_cents' => 77777]);
        Product::factory()->create(['name' => 'Secret Draft', 'status' => 'draft']);
        $company = Company::factory()->approved()->create();
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $public->id, 'unit_price_cents' => 999]);

        // Even a signed-in approved buyer's chat must not carry their private prices to the model.
        $this->actingAs($this->customer($company));
        $this->postJson('/assistant/messages', ['message' => 'Prices?'])->assertOk();
        $system = $fake->calls[0]['system'];

        $this->assertStringContainsString('Public Wallet [SKU PUB-1] /products/'.$public->slug, $system);
        $this->assertStringContainsString('From $12.50', $system);
        $this->assertStringContainsString('MOQ 10', $system);
        $this->assertStringContainsString('Trade Tote', $system);
        $this->assertStringContainsString('Trade price', $system);
        $this->assertStringNotContainsString('777.77', $system);
        $this->assertStringNotContainsString('9.99', $system);
        $this->assertStringNotContainsString('Secret Draft', $system);
        $this->assertStringContainsString('We ship samples worldwide within 7 days.', $system);
        $this->assertStringContainsString('/quote', $system);
    }

    public function test_api_key_is_never_sent_to_the_browser(): void
    {
        config(['services.anthropic.key' => 'sk-ant-super-secret-value', 'store.assistant.provider' => 'anthropic']);
        $this->app->forgetInstance(ChatProvider::class);
        $this->enable();
        $this->assertTrue(app(ChatProvider::class)->isConfigured());

        foreach (['/', '/products', '/quote'] as $url) {
            $this->get($url)->assertDontSee('sk-ant-super-secret-value')->assertSee('data-testid="chat-status-online"', false);
        }
        $this->actingAs($this->staff())->get('/admin/settings')->assertDontSee('sk-ant-super-secret-value');
    }

    public function test_admin_configures_chatbot_independently_of_whatsapp(): void
    {
        $this->app->instance(ChatProvider::class, new NullChatProvider);
        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)
            ->assertSee('AI provider not configured')
            ->set('s.chatbot_enabled', true)->set('s.chatbot_intro', '')->call('save')->assertHasErrors('s.chatbot_intro')
            ->set('s.chatbot_intro', 'Need a hand? Ask away.')->set('s.chatbot_name', 'Hide Helper')
            ->set('s.chatbot_teaser', false)->set('s.chatbot_teaser_delay', 2)
            ->set('s.whatsapp_enabled', false)
            ->call('save')->assertHasNoErrors();

        app(Settings::class)->flush();
        $this->assertTrue(setting('chatbot.enabled'));
        $this->assertFalse(setting('whatsapp.enabled'));
        $this->get('/')->assertSee('Need a hand? Ask away.')->assertSee('Hide Helper')
            ->assertSee('data-testid="chat-widget"', false)->assertDontSee('data-testid="whatsapp-button"', false);

        Livewire::test(SettingsForm::class)->set('s.chatbot_enabled', false)->set('s.whatsapp_enabled', true)->call('save');
        app(Settings::class)->flush();
        $this->get('/')->assertDontSee('data-testid="chat-widget"', false)->assertSee('data-testid="whatsapp-button"', false);
    }

    public function test_settings_save_rebuilds_the_assistant_context(): void
    {
        $fake = $this->fakeProvider();
        $this->enable();
        $this->postJson('/assistant/messages', ['message' => 'x']);
        $this->assertStringNotContainsString('Brand Two', $fake->calls[0]['system']);

        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)->set('s.brand_name', 'Brand Two')->call('save')->assertHasNoErrors();
        auth()->logout();
        app(Settings::class)->flush();
        $this->postJson('/assistant/messages', ['message' => 'y']);
        $this->assertStringContainsString('Brand Two', $fake->calls[1]['system']);
    }

    public function test_widget_is_not_on_admin_pages(): void
    {
        $this->fakeProvider();
        $this->enable();
        $this->actingAs($this->staff())->get('/admin')->assertDontSee('data-testid="chat-widget"', false);
    }
}
