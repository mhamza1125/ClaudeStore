<?php

namespace Tests\Feature\Pricing;

use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Pricing\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Negotiated prices apply from valid_from through valid_until, both inclusive. The dates are
 * business dates (Asia/Karachi, owner decision D7): audit F-13.
 */
class NegotiatedValidityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create(['base_price_cents' => 2000]);
    }

    private function priceWith(array $window): ?int
    {
        $company = Company::factory()->approved()->create(); // a fresh buyer per case, so windows never overlap
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->product->id, 'unit_price_cents' => 1500] + $window);

        return app(PriceResolver::class)->resolve($this->product->fresh(), null, 1, $company)->unitCents;
    }

    public function test_window_is_inclusive_on_both_days(): void
    {
        // Midday UTC is the same calendar day in UTC and in Karachi.
        $this->travelTo(Carbon::parse('2026-03-10 07:00:00', 'UTC'));

        $this->assertSame(1500, $this->priceWith(['valid_from' => '2026-03-10', 'valid_until' => '2026-03-10']), 'single-day window');
        $this->assertSame(1500, $this->priceWith(['valid_until' => '2026-03-10']), 'last day applies');
        $this->assertSame(2000, $this->priceWith(['valid_until' => '2026-03-09']), 'day after the window');
        $this->assertSame(2000, $this->priceWith(['valid_from' => '2026-03-11']), 'day before the window');
    }

    public function test_window_follows_the_business_calendar_day(): void
    {
        // 20:00 UTC on 10 March is 01:00 on 11 March in Asia/Karachi.
        $this->travelTo(Carbon::parse('2026-03-10 20:00:00', 'UTC'));

        $this->assertSame(2000, $this->priceWith(['valid_until' => '2026-03-10']), 'ended yesterday in business time');
        $this->assertSame(1500, $this->priceWith(['valid_from' => '2026-03-11']), 'started today in business time');
    }
}
