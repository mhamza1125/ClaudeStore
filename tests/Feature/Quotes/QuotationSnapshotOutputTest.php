<?php

namespace Tests\Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Livewire\Admin\QuoteWorkspace;
use App\Mail\QuotationSent;
use App\Models\Company;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Quotes\QuotationDocument;
use App\Services\QuotePdf;
use App\Services\QuoteWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit F-02: everything the customer sees (secure-link page, portal page, PDF, quotation
 * email and its PDF) is rendered from the snapshot of the version they can act on, never
 * from staff's unsent edits. Staff screens and the staff PDF show live data, labelled draft.
 */
class QuotationSnapshotOutputTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $buyer;

    private Quote $quote;

    /** @var array<int, QuotationSent> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->admin = $this->staff();
        $this->buyer = $this->customer(Company::factory()->create());
        $product = Product::factory()->create(['sku' => 'SNAP-WALLET']);
        Product::factory()->create(['sku' => 'NEW-LINE-SKU']);
        $this->quote = Quote::factory()->create(['user_id' => $this->buyer->id, 'company_id' => $this->buyer->company_id]);
        $this->quote->items()->create(['product_id' => $product->id, 'sku' => 'SNAP-WALLET', 'product_name' => 'Snapshot Wallet', 'quantity' => 150]);
    }

    private function workspace()
    {
        $this->actingAs($this->admin);

        return Livewire::test(QuoteWorkspace::class, ['quote' => $this->quote->fresh()]);
    }

    /** Price at 12.50 and send version 1; returns the secure link. */
    private function sendV1(): string
    {
        $item = $this->quote->items()->first();
        $this->workspace()
            ->set("lines.{$item->id}.unit_price", '12.50')
            ->set('terms.valid_until', now()->addDays(14)->toDateString())
            ->call('send')->assertHasNoErrors();

        return $this->lastLink();
    }

    private function lastLink(): string
    {
        $this->sent = [];
        Mail::assertQueued(QuotationSent::class, function (QuotationSent $mail) {
            $this->sent[] = $mail;

            return true;
        });
        auth()->logout();

        return end($this->sent)->link;
    }

    /** Staff change the price to 9.99 and add a line, but do not send. */
    private function editWithoutSending(): void
    {
        $item = $this->quote->items()->first();
        $this->workspace()
            ->set("lines.{$item->id}.unit_price", '9.99')
            ->call('save')->assertHasNoErrors()
            ->set('newSku', 'NEW-LINE-SKU')->set('newQty', 3)->call('addLine')->assertHasNoErrors();
        auth()->logout();
        $this->assertTrue($this->quote->fresh()->has_unsent_changes);
    }

    private function spyPdf(): object
    {
        $spy = new class extends QuotePdf
        {
            public ?QuotationDocument $doc = null;

            public function download(QuotationDocument $doc)
            {
                $this->doc = $doc;

                return response('pdf-spy');
            }
        };
        $this->app->instance(QuotePdf::class, $spy);

        return $spy;
    }

    public function test_secure_link_page_and_pdf_show_the_sent_version_while_staff_revise(): void
    {
        $link = $this->sendV1();
        $this->editWithoutSending();

        $this->get($link)->assertOk()
            ->assertSee('$12.50')->assertDontSee('$9.99')->assertDontSee('NEW-LINE-SKU')
            ->assertSee('This quotation is being revised');

        $spy = $this->spyPdf();
        $this->get($link.'/pdf')->assertOk();
        $this->assertSame(1, $spy->doc->version);
        $this->assertFalse($spy->doc->isDraft);
        $this->assertSame([1250], $spy->doc->items->pluck('unit_price_cents')->all());
    }

    public function test_portal_page_and_pdf_show_the_sent_version(): void
    {
        $this->sendV1();
        $this->editWithoutSending();
        $this->actingAs($this->buyer);

        $this->get('/account/quotes/'.$this->quote->reference)->assertOk()
            ->assertSee('$12.50')->assertDontSee('$9.99')->assertDontSee('NEW-LINE-SKU')
            ->assertSee('This quotation is being revised');

        $spy = $this->spyPdf();
        $this->get('/account/quotes/'.$this->quote->reference.'/pdf')->assertOk();
        $this->assertSame([1250], $spy->doc->items->pluck('unit_price_cents')->all());
    }

    public function test_queued_email_carries_the_version_so_a_late_worker_sends_the_sent_figures(): void
    {
        $this->sendV1();
        $mail = $this->sent[0];
        $this->editWithoutSending();

        $this->assertSame(1, $mail->version->version);
        $html = $mail->render();
        $this->assertStringContainsString('$12.50', $html);
        $this->assertStringNotContainsString('$9.99', $html);
        $this->assertStringNotContainsString('NEW-LINE-SKU', $html);
    }

    public function test_staff_see_live_figures_and_the_staff_pdf_is_labelled_draft(): void
    {
        $this->sendV1();
        $this->editWithoutSending();

        $this->actingAs($this->admin);
        $this->get('/admin/quotes/'.$this->quote->reference)->assertOk()->assertSee('NEW-LINE-SKU');

        $spy = $this->spyPdf();
        $this->get('/admin/quotes/'.$this->quote->reference.'/pdf')->assertOk();
        $this->assertTrue($spy->doc->isDraft);
        $this->assertSame('Draft - unsent changes', $spy->doc->draftLabel);
        $this->assertContains(999, $spy->doc->items->pluck('unit_price_cents')->all());
        $this->assertContains('NEW-LINE-SKU', $spy->doc->items->pluck('sku')->all());
    }

    public function test_resending_shows_version_2_and_acceptance_uses_the_sent_version(): void
    {
        $v1 = $this->sendV1();
        $this->editWithoutSending();

        // Revised and unsent: the customer cannot accept yet.
        $this->post($v1.'/accept', ['version' => 1, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasErrors('response');

        $this->workspace()->call('send')->assertHasNoErrors();
        $v2 = $this->lastLink();

        $this->get($v1)->assertNotFound(); // old link unchanged behaviour: rotated away
        $this->get($v2)->assertOk()->assertSee('$9.99')->assertSee('NEW-LINE-SKU')->assertDontSee('This quotation is being revised');

        $this->post($v2.'/accept', ['version' => 2, 'name' => 'Jane', 'confirm' => 1])->assertSessionHasNoErrors();
        $order = app(QuoteWorkflow::class)->convertToOrder($this->quote->fresh(), $this->admin);
        $snapshot = $this->quote->versions()->where('version', 2)->value('snapshot');
        $this->assertSame($snapshot['totals']['total_cents'], $order->total_cents);
        $this->assertSame(QuoteStatus::Converted, $this->quote->fresh()->status);
    }

    public function test_real_pdfs_render_for_customer_and_staff_documents(): void
    {
        $this->sendV1();
        $this->editWithoutSending();
        $quote = $this->quote->fresh();
        $pdf = app(QuotePdf::class);

        $this->assertStringStartsWith('%PDF', $pdf->render(QuotationDocument::fromVersion($quote, $quote->sentVersion())));
        $this->assertStringStartsWith('%PDF', $pdf->render(QuotationDocument::live($quote)));
        $html = view('pdf.quote', ['doc' => QuotationDocument::live($quote)])->render();
        $this->assertStringContainsString('Draft - unsent changes', $html);
        $this->assertStringNotContainsString('Draft', view('pdf.quote', ['doc' => QuotationDocument::fromVersion($quote, $quote->sentVersion())])->render());
    }

    public function test_new_versions_store_the_customer_block_and_terms_text(): void
    {
        $this->sendV1();
        $snapshot = $this->quote->versions()->where('version', 1)->value('snapshot');

        $this->assertSame(2, $snapshot['schema']);
        $this->assertSame($this->quote->company_name, $snapshot['customer']['company_name']);
        $this->assertArrayHasKey('issued_at', $snapshot);
        $this->assertNotEmpty($snapshot['terms_text']['payment']);
        $this->assertArrayHasKey('tax_label', $snapshot);
    }

    public function test_a_legacy_snapshot_renders_its_own_figures_and_never_falls_back_to_live_data(): void
    {
        $quote = Quote::factory()->create(['status' => QuoteStatus::AwaitingResponse, 'version' => 1, 'valid_until' => now()->addDays(5),
            'access_token_hash' => hash('sha256', 'legacy'), 'po_reference' => 'PO-LIVE-ONLY', 'total_cents' => 111100]);
        $quote->items()->create(['sku' => 'LIVE-SKU', 'product_name' => 'Live Item', 'quantity' => 1, 'unit_price_cents' => 111100, 'line_total_cents' => 111100]);
        $quote->versions()->create(['version' => 1, 'snapshot' => [ // shape written before schema 2
            'items' => [['id' => 1, 'product_id' => null, 'variant_id' => null, 'sku' => 'OLD-SKU', 'product_name' => 'Old Item', 'variant_name' => null,
                'quantity' => 2, 'customization' => null, 'unit_price_cents' => 4321, 'discount_bps' => 0, 'line_total_cents' => 8642]],
            'totals' => ['subtotal_cents' => 8642, 'discount_cents' => 0, 'shipping_cents' => 0, 'additional_charges_cents' => 0, 'additional_charges_label' => null,
                'tax_rate_bps' => 0, 'tax_cents' => 0, 'total_cents' => 8642, 'currency' => 'USD'],
            'valid_until' => now()->addDays(5)->toDateString(), 'payment_terms' => 'Legacy terms', 'delivery_terms' => null, 'customer_notes' => null,
        ]]);

        $this->get("/q/{$quote->reference}/legacy")->assertOk()
            ->assertSee('OLD-SKU')->assertSee('$43.21')->assertSee('$86.42')->assertSee('Legacy terms')
            ->assertDontSee('LIVE-SKU')->assertDontSee('$1,111.00')->assertDontSee('PO-LIVE-ONLY');
    }
}
