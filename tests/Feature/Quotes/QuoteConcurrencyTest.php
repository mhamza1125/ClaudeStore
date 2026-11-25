<?php

namespace Tests\Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Livewire\Admin\QuoteWorkspace;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit F-12: a save made from a stale screen fails with a clear message and writes nothing;
 * saving after the customer accepted (or after conversion) is refused.
 */
class QuoteConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Quote $quote;

    private int $itemId;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $product = Product::factory()->create();
        $this->quote = Quote::factory()->create();
        $this->itemId = $this->quote->items()->create(['product_id' => $product->id, 'sku' => 'CONC-1', 'product_name' => 'Concurrent', 'quantity' => 10])->id;
        $this->actingAs($this->staff());
    }

    private function open()
    {
        return Livewire::test(QuoteWorkspace::class, ['quote' => $this->quote->fresh()]);
    }

    private function unitPrice(): ?int
    {
        return $this->quote->items()->whereKey($this->itemId)->value('unit_price_cents');
    }

    public function test_second_staff_member_saving_a_stale_screen_is_refused_without_writing(): void
    {
        $a = $this->open();
        $b = $this->open();

        $a->set("lines.{$this->itemId}.unit_price", '10.00')->call('save')->assertHasNoErrors();
        $b->set("lines.{$this->itemId}.unit_price", '11.00')->set('terms.shipping', '99.00')->call('save')->assertHasErrors('stale');

        $this->assertSame(1000, $this->unitPrice());
        $this->assertSame(0, (int) $this->quote->fresh()->shipping_cents);
    }

    public function test_a_fresh_screen_can_save_again_after_reloading(): void
    {
        $a = $this->open();
        $a->set("lines.{$this->itemId}.unit_price", '10.00')->call('save')->assertHasNoErrors();
        $a->set("lines.{$this->itemId}.unit_price", '12.00')->call('save')->assertHasNoErrors();
        $this->open()->set("lines.{$this->itemId}.unit_price", '13.00')->call('save')->assertHasNoErrors();

        $this->assertSame(1300, $this->unitPrice());
    }

    public function test_line_changes_made_elsewhere_also_make_a_screen_stale(): void
    {
        Product::factory()->create(['sku' => 'CONC-2']);
        $a = $this->open();
        $this->open()->set('newSku', 'CONC-2')->set('newQty', 1)->call('addLine')->assertHasNoErrors();

        $a->set("lines.{$this->itemId}.unit_price", '10.00')->call('save')->assertHasErrors('stale');
        $this->assertNull($this->unitPrice());
    }

    public function test_saving_after_the_customer_accepted_is_refused(): void
    {
        $this->open()->set("lines.{$this->itemId}.unit_price", '10.00')
            ->set('terms.valid_until', now()->addDays(10)->toDateString())->call('send')->assertHasNoErrors();
        $stale = $this->open();

        app(QuoteWorkflow::class)->respond($this->quote->fresh(), true, 1, 'Buyer', null, null);
        $this->assertSame(QuoteStatus::Accepted, $this->quote->fresh()->status);

        $stale->set("lines.{$this->itemId}.unit_price", '5.00')->call('save')->assertHasErrors();
        $this->assertSame(1000, $this->unitPrice());
    }

    public function test_workflow_rejects_a_stale_lock_version_directly(): void
    {
        $workflow = app(QuoteWorkflow::class);
        $quote = $this->quote->fresh();
        $terms = ['discount_cents' => 0, 'shipping_cents' => 0, 'additional_charges_cents' => 0, 'tax_rate_bps' => 0, 'valid_until' => null];

        $loaded = $quote->lock_version; // what a screen opened now would hold

        $workflow->saveCommercials($quote, [$this->itemId => ['quantity' => 10, 'unit_price_cents' => 700, 'discount_bps' => 0]], $terms, auth()->user(), $loaded);
        // Saving bumps it; here it also moved pending → quoted, which bumps it again.
        $this->assertGreaterThan($loaded, $quote->fresh()->lock_version);

        try {
            $workflow->saveCommercials($quote, [$this->itemId => ['quantity' => 10, 'unit_price_cents' => 800, 'discount_bps' => 0]], $terms, auth()->user(), $loaded);
            $this->fail('A stale lock version must be refused.');
        } catch (ValidationException $e) {
            $this->assertSame(QuoteWorkflow::STALE_MESSAGE, $e->errors()['stale'][0]);
        }
        $this->assertSame(700, $this->unitPrice(), 'nothing written by the stale save');
    }
}
