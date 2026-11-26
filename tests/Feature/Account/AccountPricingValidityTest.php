<?php

namespace Tests\Feature\Account;

use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Pricing\PriceResolver;
use App\Support\BusinessTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Portal → Account pricing lists only the negotiated prices that are valid today, by the same
 * rule as PriceResolver: valid_from <= today <= valid_until, on business dates (cleanup D-020).
 */
class AccountPricingValidityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_negotiated_prices_valid_today_are_listed_matching_the_resolver(): void
    {
        // 20:00 UTC on 10 March is 01:00 on 11 March in the business time zone (Asia/Karachi).
        $this->travelTo(Carbon::parse('2026-03-10 20:00:00', 'UTC'));
        $today = BusinessTime::today();
        $this->assertSame('2026-03-11', $today);
        $tomorrow = Carbon::parse($today)->addDay()->toDateString();
        $yesterday = Carbon::parse($today)->subDay()->toDateString();

        $company = Company::factory()->approved()->create();
        $windows = [
            'Future Wallet' => ['valid_from' => $tomorrow],
            'Today Wallet' => ['valid_from' => $today, 'valid_until' => $today],
            'Expired Wallet' => ['valid_until' => $yesterday],
            'Open Wallet' => [],
        ];
        $products = [];
        foreach ($windows as $name => $window) {
            $products[$name] = Product::factory()->create(['name' => $name, 'base_price_cents' => 2000]);
            CustomerPrice::create(['company_id' => $company->id, 'product_id' => $products[$name]->id, 'unit_price_cents' => 1500] + $window);
        }

        $listed = $this->actingAs($this->customer($company))->get('/account/pricing')->assertOk()
            ->viewData('negotiated')->map(fn (CustomerPrice $p) => $p->product->name)->sort()->values()->all();

        $this->assertSame(['Open Wallet', 'Today Wallet'], $listed);

        // Same answer as the price engine for every window.
        foreach ($products as $name => $product) {
            $applies = app(PriceResolver::class)->resolve($product->fresh(), null, 1, $company)->unitCents === 1500;
            $this->assertSame($applies, in_array($name, $listed, true), "$name: portal list and PriceResolver disagree");
        }
    }
}
