<?php

namespace Tests\Feature\Assistant;

use App\Assistant\AssistantManager;
use App\Assistant\AssistantUnavailable;
use App\Assistant\Contracts\ChatProvider;
use App\Assistant\Providers\CloudflareChatProvider;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** Cloudflare Workers AI REST integration, with the HTTP layer faked (no network). */
class CloudflareChatProviderTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'cf-test-token-should-never-leak';

    private const URL = 'https://api.cloudflare.com/client/v4/accounts/acc123/ai/run/@cf/meta/llama-3.3-70b-instruct-fp8-fast';

    private function provider(?string $account = 'acc123', ?string $token = self::TOKEN, string $model = '@cf/meta/llama-3.3-70b-instruct-fp8-fast'): CloudflareChatProvider
    {
        return new CloudflareChatProvider($account, $token, $model, maxTokens: 512, temperature: 0.3, timeout: 5);
    }

    public function test_sends_an_authenticated_request_and_parses_the_workers_ai_response(): void
    {
        Http::fake([self::URL => Http::response(['success' => true, 'result' => ['response' => "  We make wallets.  \n"], 'errors' => []])]);

        $reply = $this->provider()->reply('SYSTEM', [
            ['role' => 'user', 'content' => 'Hi'],
            ['role' => 'assistant', 'content' => 'Hello!'],
            ['role' => 'user', 'content' => 'What do you make?'],
        ]);

        $this->assertSame('We make wallets.', $reply);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === self::URL
                && $request->hasHeader('Authorization', 'Bearer '.self::TOKEN)
                && $request['max_tokens'] === 512
                && $request['temperature'] === 0.3
                && $request['messages'] === [
                    ['role' => 'system', 'content' => 'SYSTEM'],
                    ['role' => 'user', 'content' => 'Hi'],
                    ['role' => 'assistant', 'content' => 'Hello!'],
                    ['role' => 'user', 'content' => 'What do you make?'],
                ];
        });
    }

    public function test_accepts_the_openai_style_response_shape(): void
    {
        Http::fake([self::URL => Http::response(['success' => true, 'result' => ['choices' => [['message' => ['role' => 'assistant', 'content' => 'MOQ is 50.']]]]])]);

        $this->assertSame('MOQ is 50.', $this->provider()->reply('S', [['role' => 'user', 'content' => 'MOQ?']]));
    }

    public function test_requires_credentials_and_a_well_formed_model(): void
    {
        $this->assertTrue($this->provider()->isConfigured());
        $this->assertFalse($this->provider(account: null)->isConfigured());
        $this->assertFalse($this->provider(token: '')->isConfigured());
        $this->assertFalse($this->provider(model: 'llama-3.3-70b')->isConfigured());
        $this->assertFalse($this->provider(model: '@cf/meta/../../evil?x=1')->isConfigured());

        Http::fake();
        $this->expectException(AssistantUnavailable::class);
        try {
            $this->provider(token: null)->reply('S', [['role' => 'user', 'content' => 'x']]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_client_errors_are_reported_without_retrying_or_logging_secrets(): void
    {
        Log::spy();
        Http::fake([self::URL => Http::response(['success' => false, 'errors' => [['code' => 10000, 'message' => 'Authentication error']]], 401)]);

        try {
            $this->provider()->reply('SECRET SYSTEM PROMPT', [['role' => 'user', 'content' => 'private visitor text']]);
            $this->fail('Expected AssistantUnavailable');
        } catch (AssistantUnavailable $e) {
            $this->assertStringContainsString('401', $e->getMessage());
            $this->assertStringContainsString('CLOUDFLARE_API_TOKEN', $e->getMessage());
            $this->assertStringContainsString('10000 Authentication error', $e->getMessage());
            $this->assertStringNotContainsString(self::TOKEN, $e->getMessage());
        }

        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []) {
            $logged = $message.json_encode($context);

            return ! str_contains($logged, self::TOKEN) && ! str_contains($logged, 'private visitor text')
                && ! str_contains($logged, 'SECRET SYSTEM PROMPT') && ($context['status'] ?? null) === 401;
        })->once();
    }

    public function test_server_errors_are_retried_once(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push(['success' => false, 'errors' => [['code' => 3040, 'message' => 'Capacity']]], 503)
            ->push(['success' => true, 'result' => ['response' => 'Recovered']])]);

        $this->assertSame('Recovered', $this->provider()->reply('S', [['role' => 'user', 'content' => 'x']]));
        Http::assertSentCount(2);
    }

    public function test_empty_or_unrecognised_responses_are_failures(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push(['success' => true, 'result' => ['response' => '   ']])
            ->push(['success' => true, 'result' => ['unexpected' => true]])
            ->push(['success' => false, 'errors' => []], 200)]);

        foreach (range(1, 3) as $_) {
            try {
                $this->provider()->reply('S', [['role' => 'user', 'content' => 'x']]);
                $this->fail('Expected AssistantUnavailable');
            } catch (AssistantUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_network_failures_become_unavailable(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $this->expectException(AssistantUnavailable::class);
        $this->provider()->reply('S', [['role' => 'user', 'content' => 'x']]);
    }

    public function test_cloudflare_is_the_default_provider_and_others_still_resolve(): void
    {
        $this->assertSame('cloudflare', config('store.assistant.provider'));
        $this->assertInstanceOf(CloudflareChatProvider::class, app(ChatProvider::class));
        $this->assertSame('anthropic', AssistantManager::make('anthropic')->name());
        $this->assertSame('null', AssistantManager::make('none')->name());
    }

    public function test_chat_endpoint_round_trip_through_cloudflare_never_exposes_the_token(): void
    {
        config(['services.cloudflare.account_id' => 'acc123', 'services.cloudflare.api_token' => self::TOKEN]);
        $this->app->forgetInstance(ChatProvider::class);
        app(Settings::class)->set(['chatbot.enabled' => true]);
        Http::fake([self::URL => Http::response(['success' => true, 'result' => ['response' => 'See /quote']])]);

        $this->postJson('/assistant/messages', ['message' => 'Can I get a quote?'])
            ->assertOk()->assertExactJson(['reply' => 'See /quote']);
        Http::assertSent(fn (Request $r) => str_contains($r['messages'][0]['content'], setting('brand.name'))
            && end($r->data()['messages'])['content'] === 'Can I get a quote?');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString(self::TOKEN, $html);
        $this->assertStringNotContainsString('acc123', $html);
    }
}
