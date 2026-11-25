<?php

namespace Tests\Feature\Security;

use App\Enums\QuoteStatus;
use App\Models\Address;
use App\Models\Company;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Characterization (audit §6): one company's customers can never reach another company's
 * quotations, PDFs, attachments, orders or addresses; inside a company only the submitter or
 * the owner may respond to a quotation.
 */
class CrossCompanyAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $memberA;

    private User $ownerB;

    private Quote $quoteA;

    protected function setUp(): void
    {
        parent::setUp();
        $a = Company::factory()->create();
        $b = Company::factory()->create();
        $this->ownerA = $this->customer($a);
        $this->memberA = $this->customer($a, 'member');
        $this->ownerB = $this->customer($b);
        $this->quoteA = Quote::factory()->create([
            'user_id' => $this->ownerA->id, 'company_id' => $a->id, 'status' => QuoteStatus::AwaitingResponse,
            'version' => 1, 'valid_until' => now()->addDays(10), 'access_token_hash' => hash('sha256', 'secret-token'),
        ]);
    }

    public function test_other_company_cannot_view_quote_pdf_respond_or_reorder(): void
    {
        $ref = $this->quoteA->reference;
        $this->actingAs($this->ownerB);
        $this->get("/account/quotes/{$ref}")->assertForbidden();
        $this->get("/account/quotes/{$ref}/pdf")->assertForbidden();
        $this->post("/account/quotes/{$ref}/accept", ['version' => 1, 'name' => 'x', 'confirm' => 1])->assertForbidden();
        $this->post("/account/quotes/{$ref}/reject", ['version' => 1])->assertForbidden();
        $this->post("/account/quotes/{$ref}/reorder")->assertForbidden();
        $this->assertSame(QuoteStatus::AwaitingResponse, $this->quoteA->fresh()->status);
    }

    public function test_company_member_can_view_but_cannot_accept_or_reject(): void
    {
        $ref = $this->quoteA->reference;
        $this->actingAs($this->memberA);
        $this->get("/account/quotes/{$ref}")->assertOk();
        $this->post("/account/quotes/{$ref}/accept", ['version' => 1, 'name' => 'x', 'confirm' => 1])->assertForbidden();
        $this->post("/account/quotes/{$ref}/reject", ['version' => 1])->assertForbidden();
        $this->assertSame(QuoteStatus::AwaitingResponse, $this->quoteA->fresh()->status);
    }

    public function test_other_company_cannot_download_an_attachment_of_a_company_quote(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('quote-attachments/x/a.pdf', '%PDF-1.4');
        $att = $this->quoteA->attachments()->create(['disk' => 'local', 'path' => 'quote-attachments/x/a.pdf', 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf', 'size' => 8]);

        $this->actingAs($this->ownerB)->get('/attachments/'.$att->id)->assertForbidden();
        $this->actingAs($this->memberA)->get('/attachments/'.$att->id)->assertOk();
    }

    public function test_other_company_cannot_view_or_reorder_an_order(): void
    {
        $order = SalesOrder::create(['reference' => 'LM-SO-2026-000101', 'quote_id' => $this->quoteA->id, 'company_id' => $this->ownerA->company_id,
            'user_id' => $this->ownerA->id, 'company_name' => 'A', 'contact_name' => 'A', 'email' => 'a@a.test', 'subtotal_cents' => 1,
            'discount_cents' => 0, 'shipping_cents' => 0, 'additional_charges_cents' => 0, 'tax_rate_bps' => 0, 'tax_cents' => 0,
            'total_cents' => 1, 'status' => 'confirmed']);

        $this->actingAs($this->ownerB);
        $this->get('/account/orders/'.$order->reference)->assertNotFound();
        $this->post('/account/orders/'.$order->reference.'/reorder')->assertNotFound();
        $this->get('/account/orders')->assertDontSee($order->reference);
    }

    public function test_other_company_cannot_change_or_remove_an_address(): void
    {
        $address = Address::create(['company_id' => $this->ownerA->company_id, 'type' => 'billing', 'line1' => '1 Street', 'city' => 'Sialkot', 'country' => 'PK']);
        $payload = ['type' => 'billing', 'line1' => 'Hijacked', 'city' => 'X', 'country' => 'PK'];

        $this->actingAs($this->ownerB);
        $this->put('/account/addresses/'.$address->id, $payload)->assertNotFound();
        $this->delete('/account/addresses/'.$address->id)->assertNotFound();
        $this->assertSame('1 Street', $address->fresh()?->line1);
    }

    public function test_secure_link_is_bound_to_its_own_quote(): void
    {
        $other = Quote::factory()->create(['status' => QuoteStatus::AwaitingResponse, 'version' => 1,
            'valid_until' => now()->addDays(10), 'access_token_hash' => hash('sha256', 'other-token')]);

        // A valid token for one quote opens nothing else.
        $this->get("/q/{$other->reference}/secret-token")->assertNotFound();
        $this->get("/q/{$this->quoteA->reference}/secret-token")->assertOk();
    }
}
