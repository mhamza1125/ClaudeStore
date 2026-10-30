<?php

namespace Tests\Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\QuoteRequestReceived;
use App\Models\Company;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteBasketService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuoteSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** Covers the pricing rules for guests with B2B price access switched off. */
    protected bool $b2bPricing = false;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        RateLimiter::clear('quote:127.0.0.1');
    }

    private function wallet(): Product
    {
        $p = Product::factory()->create(['name' => 'Classic Bifold Wallet', 'sku' => 'AH-WAL-001', 'base_price_cents' => 1800]);
        foreach ([1 => 1800, 25 => 1500, 100 => 1250, 250 => 1000] as $min => $cents) {
            PriceTier::create(['product_id' => $p->id, 'min_qty' => $min, 'unit_price_cents' => $cents]);
        }

        return $p;
    }

    public function test_guest_can_submit_a_mixed_product_quote(): void
    {
        $wallet = $this->wallet();
        $belt = Product::factory()->create(['name' => 'Belt', 'base_price_cents' => 2200, 'moq' => 12]);
        $basket = app(QuoteBasketService::class);
        $basket->add($wallet, null, 150, 'Blind emboss logo');
        $basket->add($belt, null, 12);

        $response = $this->post('/quote', $this->quoteForm([
            'attachments' => [UploadedFile::fake()->create('artwork.pdf', 200, 'application/pdf')],
            // Attempted client-side price injection must be ignored.
            'unit_price_cents' => 1, 'items' => [['unit_price_cents' => 1]],
        ]));

        $response->assertRedirect(route('quote.confirmation'));
        $quote = Quote::query()->with('items', 'attachments')->sole();
        $this->assertMatchesRegularExpression('/^LM-Q-\d{4}-\d{6}$/', $quote->reference);
        $this->assertSame(QuoteStatus::Pending, $quote->status);
        $this->assertCount(2, $quote->items);

        $line = $quote->items->firstWhere('sku', 'AH-WAL-001');
        $this->assertSame(150, $line->quantity);
        $this->assertSame(1250, $line->indicative_unit_price_cents, 'server-side tier price, not client input');
        $this->assertSame('Blind emboss logo', $line->customization);
        $this->assertNull($line->unit_price_cents, 'submitted quotes carry no final price');
        $this->assertSame(2200, $quote->items->firstWhere('product_name', 'Belt')->indicative_unit_price_cents);

        $this->assertCount(1, $quote->attachments);
        Storage::disk('local')->assertExists($quote->attachments->first()->path);
        $this->assertSame(0, $basket->count(), 'basket cleared');

        Mail::assertQueued(QuoteRequestReceived::class, fn ($m) => $m->hasTo('jane@acme.test'));
        Mail::assertQueued(NewQuoteRequestAlert::class);

        $this->get(route('quote.confirmation'))->assertOk()->assertSee($quote->reference);
    }

    public function test_references_are_sequential(): void
    {
        foreach (range(1, 2) as $i) {
            $this->post('/quote', $this->quoteForm(['message' => 'General inquiry about private label']));
        }
        $refs = Quote::query()->orderBy('id')->pluck('reference');
        $this->assertSame(sprintf('LM-Q-%d-000001', now()->year), $refs[0]);
        $this->assertSame(sprintf('LM-Q-%d-000002', now()->year), $refs[1]);
    }

    public function test_signed_in_buyer_quote_is_linked_to_company(): void
    {
        $company = Company::factory()->approved()->create();
        $user = $this->customer($company);
        $this->actingAs($user);
        app(QuoteBasketService::class)->add($this->wallet(), null, 10);

        $this->post('/quote', $this->quoteForm())->assertRedirect();
        $quote = Quote::query()->sole();
        $this->assertSame($user->id, $quote->user_id);
        $this->assertSame($company->id, $quote->company_id);
    }

    public function test_hidden_prices_are_not_recorded_as_indicative(): void
    {
        app(QuoteBasketService::class)->add(Product::factory()->create(['public_pricing' => false, 'base_price_cents' => 9900]), null, 5);
        $this->post('/quote', $this->quoteForm())->assertRedirect();
        $this->assertNull(Quote::query()->sole()->items->first()->indicative_unit_price_cents);
    }

    public function test_empty_request_is_rejected(): void
    {
        $this->post('/quote', $this->quoteForm())->assertSessionHasErrors('items');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_required_fields_and_formats_are_validated(): void
    {
        $this->post('/quote', $this->quoteForm(['company_name' => '', 'email' => 'not-an-email', 'phone' => 'call me', 'country' => 'XX', 'website' => 'javascript:alert(1)', 'message' => 'x']))
            ->assertSessionHasErrors(['company_name', 'email', 'phone', 'country', 'website']);
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_unsafe_attachments_are_rejected(): void
    {
        $this->post('/quote', $this->quoteForm([
            'message' => 'See attached',
            'attachments' => [UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')],
        ]))->assertSessionHasErrors('attachments.0');

        $this->post('/quote', $this->quoteForm([
            'message' => 'See attached',
            'attachments' => [UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf')],
        ]))->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_honeypot_and_too_fast_submissions_are_not_saved(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->post('/quote', $this->quoteForm(['message' => 'Hi', 'fax_number' => 'bot']))->assertRedirect(route('home'));
        $this->post('/quote', $this->quoteForm(['message' => 'Hi', 'form_started' => encrypt(now()->timestamp)]))->assertSessionHasErrors('form');
        $this->post('/quote', $this->quoteForm(['message' => 'Hi', 'form_started' => 'tampered']))->assertSessionHasErrors('form');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_submissions_are_rate_limited(): void
    {
        config(['store.quote_rate_limit_per_hour' => 2]);
        $this->travel(0)->seconds();
        $this->post('/quote', $this->quoteForm(['message' => 'one']))->assertRedirect();
        $this->post('/quote', $this->quoteForm(['message' => 'two']))->assertRedirect();
        $this->post('/quote', $this->quoteForm(['message' => 'three']))->assertStatus(429);
    }

    public function test_quantity_rules_and_minimum_order_value_are_rechecked_on_submit(): void
    {
        $p = Product::factory()->create(['base_price_cents' => 1000]);
        app(QuoteBasketService::class)->add($p, null, 2);
        app(Settings::class)->set(['order.min_value_cents' => 5000]);

        $this->post('/quote', $this->quoteForm())->assertSessionHasErrors('items');

        // MOQ raised after the item was added to the basket
        app(Settings::class)->set(['order.min_value_cents' => 0]);
        $p->update(['moq' => 10]);
        $this->post('/quote', $this->quoteForm())->assertSessionHasErrors('items');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_confirmation_page_requires_the_submitting_session(): void
    {
        Quote::factory()->create();
        $this->get(route('quote.confirmation'))->assertNotFound();
    }
}
