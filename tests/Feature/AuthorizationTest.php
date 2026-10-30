<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuoteWorkspace;
use App\Models\Company;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_customers_cannot_reach_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($this->customer());
        foreach (['/admin', '/admin/quotes', '/admin/products', '/admin/settings', '/admin/customers', '/admin/staff', '/admin/audit-log'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_sales_rep_permissions(): void
    {
        $rep = $this->staff('sales-rep');
        $other = $this->staff('sales-rep');
        $mine = Quote::factory()->create(['assigned_to' => $rep->id]);
        $unassigned = Quote::factory()->create();
        $theirs = Quote::factory()->create(['assigned_to' => $other->id]);

        $this->actingAs($rep);
        $this->get('/admin/quotes')->assertOk()->assertSee($mine->reference)->assertSee($unassigned->reference)->assertDontSee($theirs->reference);
        $this->get('/admin/quotes/'.$theirs->reference)->assertForbidden();
        $this->get('/admin/quotes/'.$mine->reference)->assertOk();
        foreach (['/admin/products', '/admin/settings', '/admin/staff', '/admin/customer-groups', '/admin/orders'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_cannot_manage_staff_but_super_admin_can(): void
    {
        $this->actingAs($this->staff('admin'))->get('/admin/staff')->assertForbidden();
        $this->actingAs($this->staff('super-admin'))->get('/admin/staff')->assertOk();
    }

    public function test_customers_only_see_their_own_or_company_quotes(): void
    {
        $acme = Company::factory()->create();
        $owner = $this->customer($acme);
        $colleague = $this->customer($acme, 'member');
        $stranger = $this->customer();
        $quote = Quote::factory()->create(['user_id' => $owner->id, 'company_id' => $acme->id]);

        $this->actingAs($owner)->get('/account/quotes/'.$quote->reference)->assertOk();
        $this->actingAs($colleague)->get('/account/quotes/'.$quote->reference)->assertOk();
        $this->actingAs($stranger)->get('/account/quotes/'.$quote->reference)->assertForbidden();
        $this->actingAs($stranger)->get('/account/quotes')->assertDontSee($quote->reference);
        $this->actingAs($stranger)->post('/account/quotes/'.$quote->reference.'/accept', ['version' => 1, 'name' => 'x', 'confirm' => 1])->assertForbidden();
        // Members can view but only the submitter/owner may respond
        $this->actingAs($colleague)->post('/account/quotes/'.$quote->reference.'/accept', ['version' => 1, 'name' => 'x', 'confirm' => 1])->assertForbidden();
    }

    public function test_attachments_are_private(): void
    {
        Storage::fake('local');
        $owner = $this->customer();
        $quote = Quote::factory()->create(['user_id' => $owner->id]);
        Storage::disk('local')->put('quote-attachments/1/a.pdf', '%PDF-1.4');
        $att = $quote->attachments()->create(['disk' => 'local', 'path' => 'quote-attachments/1/a.pdf', 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf', 'size' => 8]);

        $this->get('/attachments/'.$att->id)->assertRedirect('/login');
        $this->actingAs($this->customer())->get('/attachments/'.$att->id)->assertForbidden();
        $this->actingAs($owner)->get('/attachments/'.$att->id)->assertOk();
        $this->actingAs($this->staff())->get('/attachments/'.$att->id)->assertOk();
        $this->assertFalse(Storage::disk('public')->exists('quote-attachments/1/a.pdf'));
    }

    public function test_orders_are_private_to_the_company(): void
    {
        $owner = $this->customer(Company::factory()->create());
        $quote = Quote::factory()->create(['user_id' => $owner->id, 'company_id' => $owner->company_id]);
        $order = SalesOrder::create(['reference' => 'LM-SO-2026-000009', 'quote_id' => $quote->id, 'company_id' => $owner->company_id, 'user_id' => $owner->id,
            'company_name' => 'x', 'contact_name' => 'x', 'email' => 'x@x.test', 'subtotal_cents' => 1, 'discount_cents' => 0, 'shipping_cents' => 0,
            'additional_charges_cents' => 0, 'tax_rate_bps' => 0, 'tax_cents' => 0, 'total_cents' => 1, 'status' => 'confirmed']);

        $this->actingAs($owner)->get('/account/orders/'.$order->reference)->assertOk();
        $this->actingAs($this->customer())->get('/account/orders/'.$order->reference)->assertNotFound();
    }

    public function test_customer_cannot_use_admin_livewire_component_directly(): void
    {
        $quote = Quote::factory()->create();
        $this->actingAs($this->customer());
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])->assertForbidden();
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        $user = $this->customer();
        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->customer();
        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_registration_creates_customer_with_company(): void
    {
        $this->post('/register', [
            'name' => 'New Buyer', 'email' => 'new@buyer.test', 'company_name' => 'Buyer Co', 'country' => 'US',
            'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
        ])->assertRedirect();
        $user = \App\Models\User::query()->where('email', 'new@buyer.test')->sole();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertSame('Buyer Co', $user->company->name);
        $this->assertSame('none', $user->company->wholesale_status);
        $this->assertTrue($user->isCompanyOwner());
    }

    public function test_theme_preference_is_saved_and_rendered(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->putJson('/preferences/theme', ['theme' => 'dark'])->assertNoContent();
        $this->assertSame('dark', $user->fresh()->theme);
        $this->putJson('/preferences/theme', ['theme' => 'neon'])->assertUnprocessable();
        $this->get('/')->assertSee('data-user-theme="dark"', false);
        $this->get('/admin')->assertForbidden();
    }

    public function test_head_script_prevents_theme_flash(): void
    {
        Product::factory()->create();
        $html = $this->get('/')->getContent();
        $this->assertLessThan(strpos($html, 'build/assets'), strpos($html, "localStorage.getItem('ah-theme')"));
    }
}
