<?php

namespace Tests\Feature\Account;

use App\Enums\QuoteStatus;
use App\Mail\WholesaleApplicationReceived;
use App\Mail\WholesaleStatusChanged;
use App\Livewire\Admin\CompanyShow;
use App\Models\Company;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteBasketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_pages_render(): void
    {
        $this->actingAs($this->customer(Company::factory()->approved()->create()));
        foreach (['/account', '/account/profile', '/account/company', '/account/pricing', '/account/quotes', '/account/orders', '/account/favorites', '/account/team'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_company_profile_creation_and_addresses(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->put('/account/company', ['name' => 'Leather Co', 'country' => 'FR'])->assertRedirect();
        $company = $user->fresh()->company;
        $this->assertSame('Leather Co', $company->name);

        $this->post('/account/addresses', ['type' => 'shipping', 'line1' => '1 Rue', 'city' => 'Paris', 'country' => 'FR', 'is_default' => 1])->assertRedirect();
        $address = $company->addresses()->sole();
        $this->assertTrue($address->is_default);

        $this->actingAs($this->customer(Company::factory()->create()))->delete('/account/addresses/'.$address->id)->assertNotFound();
    }

    public function test_wholesale_application_and_approval(): void
    {
        Mail::fake();
        $company = Company::factory()->create();
        $owner = $this->customer($company);
        $this->actingAs($owner)->post('/account/company/wholesale', ['wholesale_application' => 'We run three boutiques in Lyon and order quarterly.'])->assertRedirect();
        $this->assertSame('pending', $company->fresh()->wholesale_status);
        Mail::assertQueued(WholesaleApplicationReceived::class);

        $this->actingAs($this->staff());
        Livewire::test(CompanyShow::class, ['company' => $company])->call('setWholesale', 'approved');
        $this->assertTrue($company->fresh()->isWholesaleApproved());
        Mail::assertQueued(WholesaleStatusChanged::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'company.wholesale_approved']);
    }

    public function test_sales_rep_cannot_approve_wholesale(): void
    {
        $company = Company::factory()->create();
        $this->actingAs($this->staff('sales-rep'));
        Livewire::test(CompanyShow::class, ['company' => $company])->call('setWholesale', 'approved')->assertForbidden();
    }

    public function test_reorder_adds_previous_lines_at_current_prices(): void
    {
        $user = $this->customer();
        $product = Product::factory()->create();
        $archived = Product::factory()->create(['status' => 'archived']);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'status' => QuoteStatus::Converted]);
        $quote->items()->create(['product_id' => $product->id, 'sku' => $product->sku, 'product_name' => $product->name, 'quantity' => 7, 'unit_price_cents' => 1]);
        $quote->items()->create(['product_id' => $archived->id, 'sku' => $archived->sku, 'product_name' => 'Old thing', 'quantity' => 2]);

        $this->actingAs($user)->post('/account/quotes/'.$quote->reference.'/reorder')->assertRedirect('/quote')->assertSessionHas('warning');
        $lines = app(QuoteBasketService::class)->lines();
        $this->assertCount(1, $lines);
        $this->assertSame(7, $lines->first()['item']->quantity);
        $this->assertSame(2000, $lines->first()['price']->unitCents, 'current catalogue price, not the old quote price');
    }

    public function test_favorites(): void
    {
        $product = Product::factory()->create(['name' => 'Saved Wallet']);
        $this->actingAs($this->customer())->post('/account/favorites/'.$product->slug)->assertRedirect();
        $this->get('/account/favorites')->assertSee('Saved Wallet');
        $this->delete('/account/favorites/'.$product->slug);
        $this->get('/account/favorites')->assertDontSee('Saved Wallet');
    }

    public function test_customer_can_accept_through_portal(): void
    {
        $user = $this->customer();
        $quote = Quote::factory()->create(['user_id' => $user->id, 'status' => QuoteStatus::AwaitingResponse, 'version' => 1, 'valid_until' => now()->addWeek(), 'total_cents' => 1000]);
        $this->actingAs($user)->get('/account/quotes/'.$quote->reference)->assertSee('Accept quotation');
        $this->post('/account/quotes/'.$quote->reference.'/accept', ['version' => 1, 'name' => 'Me', 'confirm' => 1])->assertSessionHasNoErrors();
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }
}
