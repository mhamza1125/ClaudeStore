<?php

namespace Tests\Feature\B2b;

use App\Assistant\BusinessContext;
use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteBasketService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** B2B price access (Admin → B2B settings): server-side visibility in every channel. */
class PriceAccessTest extends TestCase
{
    use RefreshDatabase;

    private Product $wallet;

    private Product $tradeOnly;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = Product::factory()->create(['name' => 'Classic Wallet', 'sku' => 'AH-WAL-001', 'base_price_cents' => 1800]);
        PriceTier::create(['product_id' => $this->wallet->id, 'min_qty' => 100, 'unit_price_cents' => 1250]);
        $this->tradeOnly = Product::factory()->create(['name' => 'Trade Belt', 'base_price_cents' => 9900, 'public_pricing' => false]);
    }

    private function b2b(array $settings = []): void
    {
        app(Settings::class)->set(array_merge([
            'b2b.enabled' => true, 'b2b.guest_prices' => true, 'b2b.require_registration' => true,
            'b2b.require_email_verification' => false, 'b2b.require_approval' => false, 'b2b.guest_inquiries' => true,
        ], $settings));
        BusinessContext::forget();
    }

    private function productPage(Product $product)
    {
        return $this->get(route('products.show', $product));
    }

    public function test_guests_see_no_prices_by_default(): void
    {
        // Reported issue: /products/dress-belt-35mm showed its (public) list price to visitors.
        // B2B price access is now on by default, without changing any product's data.
        $this->assertTrue($this->wallet->public_pricing);
        $this->productPage($this->wallet)->assertOk()->assertDontSee('$18.00')->assertDontSee('$12.50')->assertSee('Wholesale prices');
        $this->get('/')->assertOk()->assertDontSee('$18.00');
        $this->getJson('/search/suggest?q=wallet')->assertJsonPath('results.0.price', 'Sign in for price');
    }

    public function test_with_b2b_off_existing_visibility_rules_apply(): void
    {
        app(Settings::class)->set(['b2b.enabled' => false]);
        $this->productPage($this->wallet)->assertOk()->assertSee('$18.00')->assertSee('$12.50');
        $this->productPage($this->tradeOnly)->assertDontSee('$99.00')->assertSee('Trade pricing');
    }

    public function test_registration_required_hides_prices_from_guests_in_every_channel(): void
    {
        $this->b2b();

        $page = $this->productPage($this->wallet)->assertOk()->assertSee('Wholesale prices')->assertSee('create a free trade account');
        foreach (['$18.00', '$12.50', '18.00', '12.50', '"offers"'] as $hidden) {
            $page->assertDontSee($hidden, false); // markup, Livewire snapshot and JSON-LD included
        }

        $this->get('/products')->assertOk()->assertSee('Sign in for price')->assertDontSee('$18.00')->assertDontSee('Price: low to high');
        $this->getJson('/search/suggest?q=wallet')->assertJsonPath('results.0.price', 'Sign in for price')
            ->assertDontSee('18.00');

        app(QuoteBasketService::class)->add($this->wallet, null, 150);
        $this->get('/quote')->assertOk()->assertDontSee('$12.50')->assertSee('Priced in your quotation');
    }

    public function test_price_sort_cannot_reveal_hidden_prices(): void
    {
        $this->b2b();
        Product::factory()->create(['name' => 'Aardvark Tote', 'base_price_cents' => 99999]);
        // Sorting by price would put the wallet first; without access the default order applies.
        $this->get('/products?sort=price_asc')->assertOk()->assertSeeInOrder(['Aardvark Tote', 'Classic Wallet']);
    }

    public function test_guests_can_see_public_prices_when_registration_is_not_required(): void
    {
        $this->b2b(['b2b.require_registration' => false, 'b2b.guest_prices' => true]);
        $this->productPage($this->wallet)->assertSee('$18.00');
        $this->productPage($this->tradeOnly)->assertDontSee('$99.00');

        $this->b2b(['b2b.require_registration' => false, 'b2b.guest_prices' => false]);
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Wholesale prices');
    }

    public function test_register_and_access_unlocks_prices_including_trade_only_products(): void
    {
        $this->b2b();
        $this->actingAs($this->customer());
        $this->productPage($this->wallet)->assertSee('$18.00');
        $this->productPage($this->tradeOnly)->assertSee('$99.00');
        $this->getJson('/search/suggest?q=wallet')->assertJsonPath('results.0.price', 'From $12.50');
    }

    public function test_register_and_verify_email(): void
    {
        $this->b2b(['b2b.require_email_verification' => true]);
        $user = User::factory()->unverified()->create()->assignRole('customer');

        $this->actingAs($user);
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Verify your email to see prices')->assertSee('Resend the verification email');
        $this->get('/account')->assertSee('Verify your email to see prices');

        $user->markEmailAsVerified();
        $this->productPage($this->wallet)->assertSee('$18.00');
    }

    public function test_register_and_wait_for_approval(): void
    {
        $this->b2b(['b2b.require_approval' => true]);
        $user = User::factory()->pendingApproval()->create()->assignRole('customer');

        $this->actingAs($user);
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Prices after approval');

        $user->update(['approval_status' => 'rejected']);
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Price on request');

        $user->update(['approval_status' => 'approved']);
        $this->productPage($this->wallet)->assertSee('$18.00');
    }

    public function test_register_verify_and_wait_for_approval_needs_both(): void
    {
        $this->b2b(['b2b.require_email_verification' => true, 'b2b.require_approval' => true]);
        $user = User::factory()->unverified()->pendingApproval()->create()->assignRole('customer');
        $this->actingAs($user);

        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Verify your email');
        $user->update(['approval_status' => 'approved']);
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Verify your email');
        $user->update(['approval_status' => 'pending']);
        $user->markEmailAsVerified();
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Prices after approval');
        $user->update(['approval_status' => 'approved']);
        $this->productPage($this->wallet)->assertSee('$18.00');
    }

    public function test_individual_overrides(): void
    {
        // "Never show prices" applies even with B2B pricing off.
        app(Settings::class)->set(['b2b.enabled' => false]);
        $blocked = $this->customer();
        $blocked->update(['price_access' => 'deny']);
        $this->actingAs($blocked);
        $this->productPage($this->wallet)->assertDontSee('$18.00')->assertSee('Price on request');
        $this->getJson('/search/suggest?q=wallet')->assertJsonPath('results.0.price', 'Price on request');

        // "Always show prices" skips verification and approval.
        $this->b2b(['b2b.require_email_verification' => true, 'b2b.require_approval' => true]);
        $vip = User::factory()->unverified()->pendingApproval()->create(['price_access' => 'allow'])->assignRole('customer');
        $this->actingAs($vip);
        $this->productPage($this->wallet)->assertSee('$18.00');
    }

    public function test_staff_always_see_prices(): void
    {
        $this->b2b(['b2b.require_approval' => true]);
        $this->actingAs($this->staff('sales-rep'));
        $this->productPage($this->tradeOnly)->assertSee('$99.00');
    }

    public function test_suspended_accounts_are_signed_out_and_cannot_sign_in(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->get('/account')->assertOk();

        $user->update(['is_active' => false]);
        $this->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_customer_specific_pricing_is_preserved_once_access_is_granted(): void
    {
        $this->b2b(['b2b.require_approval' => true]);
        $company = Company::factory()->approved()->create();
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'min_qty' => 1, 'unit_price_cents' => 1111]);
        $user = User::factory()->pendingApproval()->create(['company_id' => $company->id, 'company_role' => 'owner'])->assignRole('customer');

        $this->actingAs($user);
        $this->productPage($this->wallet)->assertDontSee('$11.11');
        $this->get('/account/pricing')->assertOk()->assertDontSee('$11.11');

        $user->update(['approval_status' => 'approved']);
        $this->productPage($this->wallet)->assertSee('$11.11')->assertSee('Your negotiated price', false);
        $this->get('/account/pricing')->assertSee('$11.11');
    }

    public function test_hidden_prices_are_not_recorded_as_indicative_on_quote_requests(): void
    {
        $this->b2b();
        app(QuoteBasketService::class)->add($this->wallet, null, 150);
        $this->post('/quote', $this->quoteForm())->assertRedirect(route('quote.confirmation'));

        $item = Quote::query()->sole()->items()->sole();
        $this->assertNull($item->indicative_unit_price_cents);
        $this->assertNull($item->indicative_price_source);
    }

    public function test_guest_inquiries_can_be_turned_off(): void
    {
        $this->b2b(['b2b.guest_inquiries' => false]);
        app(QuoteBasketService::class)->add($this->wallet, null, 150);

        $this->get('/quote')->assertSee('data-testid="inquiry-sign-in"', false)->assertDontSee('Submit quote request');
        $this->post('/quote', $this->quoteForm())->assertRedirect(route('login'));
        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('quote_basket_items', 1); // the list is kept for after sign-in

        $this->actingAs($this->customer());
        $this->get('/quote')->assertSee('Submit quote request');
    }

    public function test_chatbot_only_knows_prices_anonymous_visitors_may_see(): void
    {
        app(Settings::class)->set(['b2b.enabled' => false]);
        BusinessContext::forget();
        $this->assertStringContainsString('$12.50', app(BusinessContext::class)->systemPrompt());

        $this->b2b();
        $prompt = app(BusinessContext::class)->systemPrompt();
        $this->assertStringNotContainsString('$12.50', $prompt);
        $this->assertStringNotContainsString('$18.00', $prompt);
        $this->assertStringContainsString('Sign in for price', $prompt);
    }
}
