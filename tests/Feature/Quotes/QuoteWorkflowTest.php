<?php

namespace Tests\Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Livewire\Admin\QuoteWorkspace;
use App\Mail\QuotationSent;
use App\Mail\QuoteResponded;
use App\Models\Company;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Services\QuoteWorkflow;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function quoteWithLine(array $attrs = []): Quote
    {
        $product = Product::factory()->create(['sku' => 'AH-WAL-001', 'base_price_cents' => 1800]);
        $quote = Quote::factory()->create($attrs);
        $quote->items()->create(['product_id' => $product->id, 'sku' => 'AH-WAL-001', 'product_name' => 'Classic Bifold Wallet', 'quantity' => 150]);

        return $quote;
    }

    /** Send through the admin workspace and capture the secure link from the queued mail. */
    private function priceAndSend(Quote $quote, string $unit = '12.50'): string
    {
        $admin = $this->staff();
        $item = $quote->items()->first();
        $this->actingAs($admin);
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])
            ->set("lines.{$item->id}.unit_price", $unit)
            ->set("lines.{$item->id}.discount", '10')
            ->set('terms.shipping', '50.00')
            ->set('terms.tax_rate', '8.25')
            ->set('terms.valid_until', now()->addDays(14)->toDateString())
            ->call('send')
            ->assertHasNoErrors();

        $link = null;
        Mail::assertQueued(QuotationSent::class, function (QuotationSent $mail) use (&$link) {
            $link = $mail->link;

            return true;
        });
        auth()->logout();

        return $link;
    }

    public function test_full_flow_from_pricing_to_sales_order(): void
    {
        $quote = $this->quoteWithLine();
        $link = $this->priceAndSend($quote);

        $quote->refresh();
        $this->assertSame(QuoteStatus::AwaitingResponse, $quote->status);
        $this->assertSame(1, $quote->version);
        $this->assertNotNull($quote->sent_at);
        // 150 × 12.50 = 1875.00 − 10% = 1687.50; + 50 shipping; tax 8.25% of 1737.50 = 143.34
        $this->assertSame(168750, $quote->subtotal_cents);
        $this->assertSame(14334, $quote->tax_cents);
        $this->assertSame(168750 + 5000 + 14334, $quote->total_cents);
        $this->assertCount(1, $quote->versions);

        // Customer opens the secure link
        $this->get($link)->assertOk()->assertSee('$1,880.84')->assertSee('Accept quotation');
        $this->get($link.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Acceptance requires explicit confirmation and a name
        $this->post($link.'/accept', ['version' => 1, 'name' => 'Jane Buyer'])->assertSessionHasErrors('confirm');
        $this->post($link.'/accept', ['version' => 1, 'name' => 'Jane Buyer', 'confirm' => '1'])->assertSessionHasNoErrors();
        $quote->refresh();
        $this->assertSame(QuoteStatus::Accepted, $quote->status);
        $this->assertSame('Jane Buyer', $quote->accepted_by_name);
        Mail::assertQueued(QuoteResponded::class);

        // Catalogue changes after acceptance must not affect the agreed prices
        Product::query()->where('sku', 'AH-WAL-001')->update(['base_price_cents' => 99999, 'name' => 'Renamed']);

        $this->actingAs($this->staff());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])->call('convert')->assertRedirect();
        $order = SalesOrder::query()->with('items')->sole();
        $this->assertMatchesRegularExpression('/^AH-SO-\d{4}-000001$/', $order->reference);
        $this->assertSame($quote->total_cents, $order->total_cents);
        $this->assertSame(1250, $order->items->first()->unit_price_cents);
        $this->assertSame(1000, $order->items->first()->discount_bps);
        $this->assertSame('Classic Bifold Wallet', $order->items->first()->product_name);
        $this->assertSame(QuoteStatus::Converted, $quote->fresh()->status);

        // Accepted/converted quotes are locked
        $this->expectException(ValidationException::class);
        app(QuoteWorkflow::class)->saveCommercials($quote->fresh(), [], ['discount_cents' => 0, 'shipping_cents' => 0, 'additional_charges_cents' => 0, 'tax_rate_bps' => 0, 'valid_until' => null], $this->staff());
    }

    public function test_wrong_token_returns_404_and_reference_alone_is_not_enough(): void
    {
        $quote = $this->quoteWithLine();
        $this->priceAndSend($quote);
        $this->get('/q/'.$quote->reference.'/not-the-token')->assertNotFound();
        $this->post('/q/'.$quote->reference.'/not-the-token/accept', ['version' => 1, 'name' => 'X', 'confirm' => 1])->assertNotFound();
        $this->assertSame(QuoteStatus::AwaitingResponse, $quote->fresh()->status);
    }

    public function test_resending_rotates_the_link_and_old_version_cannot_be_accepted(): void
    {
        $quote = $this->quoteWithLine();
        $first = $this->priceAndSend($quote);
        $second = $this->priceAndSend($quote->fresh(), '12.00');

        $this->get($first)->assertNotFound();
        $this->post($second.'/accept', ['version' => 1, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasErrors('response');
        $this->post($second.'/accept', ['version' => 2, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasNoErrors();
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_unsent_revisions_block_acceptance(): void
    {
        $quote = $this->quoteWithLine();
        $link = $this->priceAndSend($quote);
        $this->actingAs($this->staff());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote->fresh()])->set('terms.shipping', '75.00')->call('save');
        auth()->logout();

        $this->assertTrue($quote->fresh()->has_unsent_changes);
        $this->post($link.'/accept', ['version' => 1, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasErrors('response');
    }

    public function test_expired_quotes_cannot_be_accepted_until_extended_and_resent(): void
    {
        $quote = $this->quoteWithLine();
        $link = $this->priceAndSend($quote);

        $this->travel(15)->days();
        $this->post($link.'/accept', ['version' => 1, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasErrors('response');
        $this->assertSame(QuoteStatus::Expired, $quote->fresh()->status);

        // Authorised extension: new validity + re-send creates version 2
        $newLink = $this->priceAndSend($quote->fresh());
        $this->post($newLink.'/accept', ['version' => 2, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasNoErrors();
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_scheduled_expiry_command(): void
    {
        $quote = $this->quoteWithLine();
        $this->priceAndSend($quote);
        $this->travel(20)->days();
        $this->artisan('quotes:expire')->expectsOutputToContain('1 quotation(s)')->assertSuccessful();
        $this->assertSame(QuoteStatus::Expired, $quote->fresh()->status);
    }

    public function test_cannot_send_without_prices_or_validity(): void
    {
        $quote = $this->quoteWithLine();
        $this->actingAs($this->staff());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])->call('send')->assertHasErrors('send');
        $this->assertSame(0, $quote->fresh()->version);
        Mail::assertNotQueued(QuotationSent::class);
    }

    public function test_cannot_convert_unaccepted_quote(): void
    {
        $quote = $this->quoteWithLine();
        $this->priceAndSend($quote);
        $this->actingAs($this->staff());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote->fresh()])->call('convert')->assertHasErrors('convert');
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_rejection_records_reason(): void
    {
        $quote = $this->quoteWithLine();
        $link = $this->priceAndSend($quote);
        $this->post($link.'/reject', ['version' => 1, 'reason' => 'Too expensive'])->assertSessionHasNoErrors();
        $quote->refresh();
        $this->assertSame(QuoteStatus::Rejected, $quote->status);
        $this->assertSame('Too expensive', $quote->rejection_reason);
    }

    public function test_workspace_defaults_come_from_settings_and_price_changes_are_audited(): void
    {
        app(Settings::class)->set(['tax.rate_bps' => 2000, 'shipping.flat_cents' => 4500]);
        $quote = $this->quoteWithLine();
        $item = $quote->items()->first();
        $this->actingAs($this->staff());

        $c = Livewire::test(QuoteWorkspace::class, ['quote' => $quote])->assertSet('terms.tax_rate', '20')->assertSet('terms.shipping', '45.00');
        $c->set("lines.{$item->id}.unit_price", '12.5')->call('save')->assertHasNoErrors();

        $this->assertSame(1250, $item->fresh()->unit_price_cents);
        $this->assertSame(QuoteStatus::Quoted, $quote->fresh()->status);
        $this->assertDatabaseHas('quote_activities', ['quote_id' => $quote->id, 'type' => 'price_change']);
    }

    public function test_apply_catalogue_prices_uses_the_quote_company_pricing(): void
    {
        $company = Company::factory()->approved()->create();
        $quote = $this->quoteWithLine(['company_id' => $company->id]);
        \App\Models\CustomerPrice::create(['company_id' => $company->id, 'product_id' => $quote->items()->first()->product_id, 'unit_price_cents' => 1111]);
        $this->actingAs($this->staff());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])->call('applySuggested');
        $this->assertSame(1111, $quote->items()->first()->unit_price_cents);
    }

    public function test_invalid_money_input_is_rejected(): void
    {
        $quote = $this->quoteWithLine();
        $item = $quote->items()->first();
        $this->actingAs($this->staff());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])
            ->set("lines.{$item->id}.unit_price", '12.505')->call('save')->assertHasErrors("lines.{$item->id}.unit_price")
            ->set("lines.{$item->id}.unit_price", '12.50')->set("lines.{$item->id}.discount", '150')->call('save')->assertHasErrors("lines.{$item->id}.discount");
        $this->assertNull($item->fresh()->unit_price_cents);
    }
}
