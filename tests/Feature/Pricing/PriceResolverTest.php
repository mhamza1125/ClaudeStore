<?php

namespace Tests\Feature\Pricing;

use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Pricing\PriceResolver;
use App\Pricing\PriceSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PriceResolverTest extends TestCase
{
    use RefreshDatabase;

    private PriceResolver $resolver;

    private Product $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PriceResolver;
        $this->wallet = Product::factory()->create(['base_price_cents' => 1800]);
        foreach ([1 => 1800, 25 => 1500, 100 => 1250, 250 => 1000] as $min => $cents) {
            PriceTier::create(['product_id' => $this->wallet->id, 'min_qty' => $min, 'unit_price_cents' => $cents]);
        }
    }

    public static function walletQuantities(): array
    {
        return [
            'qty 1' => [1, 1800], 'qty 10' => [10, 1800], 'qty 24' => [24, 1800],
            'qty 25' => [25, 1500], 'qty 50' => [50, 1500], 'qty 99' => [99, 1500],
            'qty 100' => [100, 1250], 'qty 150' => [150, 1250], 'qty 249' => [249, 1250],
            'qty 250' => [250, 1000], 'qty 300' => [300, 1000], 'qty 10000' => [10000, 1000],
        ];
    }

    #[DataProvider('walletQuantities')]
    public function test_illustrative_wallet_tiers(int $qty, int $expected): void
    {
        $result = $this->resolver->resolve($this->wallet, null, $qty);

        $this->assertSame($expected, $result->unitCents);
        $this->assertSame(PriceSource::TierProduct, $result->source);
        $this->assertSame($expected * $qty, $result->lineTotalCents());
        $this->assertTrue($result->visible);
    }

    public function test_ladder_marks_the_active_tier(): void
    {
        $result = $this->resolver->resolve($this->wallet, null, 150);

        $this->assertSame([
            ['min' => 1, 'max' => 24, 'unit_cents' => 1800, 'active' => false],
            ['min' => 25, 'max' => 99, 'unit_cents' => 1500, 'active' => false],
            ['min' => 100, 'max' => 249, 'unit_cents' => 1250, 'active' => true],
            ['min' => 250, 'max' => null, 'unit_cents' => 1000, 'active' => false],
        ], $result->ladder);
        $this->assertSame(250, $result->nextTier()['min']);
    }

    public function test_variant_tiers_override_product_tiers(): void
    {
        $variant = ProductVariant::factory()->for($this->wallet)->create();
        PriceTier::create(['product_id' => $this->wallet->id, 'variant_id' => $variant->id, 'min_qty' => 1, 'unit_price_cents' => 2000]);
        PriceTier::create(['product_id' => $this->wallet->id, 'variant_id' => $variant->id, 'min_qty' => 50, 'unit_price_cents' => 1700]);

        $this->assertSame(2000, $this->resolver->resolve($this->wallet, $variant, 30)->unitCents);
        $result = $this->resolver->resolve($this->wallet, $variant, 300);
        $this->assertSame(1700, $result->unitCents);
        $this->assertSame(PriceSource::TierVariant, $result->source);

        $other = ProductVariant::factory()->for($this->wallet)->create();
        $this->assertSame(1000, $this->resolver->resolve($this->wallet, $other, 300)->unitCents);
    }

    public function test_base_price_fallback_prefers_variant_price(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 5000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_cents' => 5500]);
        $plain = ProductVariant::factory()->for($product)->create(['price_cents' => null]);

        $this->assertSame(PriceSource::BaseVariant, $this->resolver->resolve($product, $variant, 3)->source);
        $this->assertSame(5500, $this->resolver->resolve($product, $variant, 3)->unitCents);
        $this->assertSame(5000, $this->resolver->resolve($product, $plain, 3)->unitCents);
        $this->assertSame(PriceSource::BaseProduct, $this->resolver->resolve($product, null, 3)->source);
    }

    public function test_full_precedence_chain_for_an_approved_company(): void
    {
        $group = CustomerGroup::factory()->create();
        $company = Company::factory()->approved()->create(['customer_group_id' => $group->id]);
        $variant = ProductVariant::factory()->for($this->wallet)->create();

        // Standard tier
        $this->assertSame(1500, $this->resolver->resolve($this->wallet, $variant, 50, $company)->unitCents);

        // Group tier beats standard tier
        PriceTier::create(['product_id' => $this->wallet->id, 'customer_group_id' => $group->id, 'min_qty' => 1, 'unit_price_cents' => 1400]);
        $r = $this->resolver->resolve($this->wallet, $variant, 50, $company);
        $this->assertSame([1400, PriceSource::GroupTierProduct], [$r->unitCents, $r->source]);

        // Negotiated product price beats group tier
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'unit_price_cents' => 1300]);
        $r = $this->resolver->resolve($this->wallet, $variant, 50, $company);
        $this->assertSame([1300, PriceSource::NegotiatedProduct], [$r->unitCents, $r->source]);

        // Negotiated variant price beats negotiated product price
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'variant_id' => $variant->id, 'unit_price_cents' => 1250]);
        $r = $this->resolver->resolve($this->wallet, $variant, 50, $company);
        $this->assertSame([1250, PriceSource::NegotiatedVariant], [$r->unitCents, $r->source]);

        // Another company is unaffected
        $other = Company::factory()->approved()->create();
        $this->assertSame(1500, $this->resolver->resolve($this->wallet, $variant, 50, $other)->unitCents);
    }

    public function test_unapproved_companies_get_no_customer_specific_pricing(): void
    {
        $group = CustomerGroup::factory()->create(['discount_bps' => 1000]);
        $company = Company::factory()->create(['customer_group_id' => $group->id, 'wholesale_status' => 'pending']);
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'unit_price_cents' => 100]);
        PriceTier::create(['product_id' => $this->wallet->id, 'customer_group_id' => $group->id, 'min_qty' => 1, 'unit_price_cents' => 200]);

        $r = $this->resolver->resolve($this->wallet, null, 50, $company);
        $this->assertSame([1500, PriceSource::TierProduct], [$r->unitCents, $r->source]);
    }

    public function test_negotiated_price_respects_validity_window_and_min_quantity(): void
    {
        $company = Company::factory()->approved()->create();
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'unit_price_cents' => 900, 'valid_until' => now()->subDay()]);
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'unit_price_cents' => 1100, 'min_qty' => 500]);
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->wallet->id, 'unit_price_cents' => 1000, 'valid_from' => now()->addWeek()]);

        $this->assertSame(1500, $this->resolver->resolve($this->wallet, null, 50, $company)->unitCents);
        $this->assertSame(1100, $this->resolver->resolve($this->wallet, null, 500, $company)->unitCents);
    }

    public function test_group_tiers_fall_through_when_quantity_below_their_first_threshold(): void
    {
        $group = CustomerGroup::factory()->create();
        $company = Company::factory()->approved()->create(['customer_group_id' => $group->id]);
        PriceTier::create(['product_id' => $this->wallet->id, 'customer_group_id' => $group->id, 'min_qty' => 100, 'unit_price_cents' => 1100]);

        $this->assertSame(1800, $this->resolver->resolve($this->wallet, null, 10, $company)->unitCents);
        $this->assertSame(1100, $this->resolver->resolve($this->wallet, null, 100, $company)->unitCents);
    }

    public function test_group_discount_applies_to_standard_and_base_prices_only(): void
    {
        $group = CustomerGroup::factory()->create(['discount_bps' => 500]);
        $company = Company::factory()->approved()->create(['customer_group_id' => $group->id]);

        $r = $this->resolver->resolve($this->wallet, null, 150, $company);
        // 1250 − 5% (62.5¢, discount rounded half up to 63¢) = 1187
        $this->assertSame(1187, $r->unitCents);
        $this->assertSame(500, $r->groupDiscountBps);
        $this->assertSame(1187, collect($r->ladder)->firstWhere('active', true)['unit_cents']);

        // Base price fallback is discounted too
        $plain = Product::factory()->create(['base_price_cents' => 2000]);
        $this->assertSame(1900, $this->resolver->resolve($plain, null, 1, $company)->unitCents);

        // A group tier is already a group-specific price, so the discount is not stacked on it
        PriceTier::create(['product_id' => $this->wallet->id, 'customer_group_id' => $group->id, 'min_qty' => 1, 'unit_price_cents' => 1000]);
        $this->assertSame(1000, $this->resolver->resolve($this->wallet, null, 150, $company)->unitCents);
    }

    public function test_hidden_and_on_request_pricing(): void
    {
        $hidden = Product::factory()->create(['public_pricing' => false, 'base_price_cents' => 9900]);
        $guest = $this->resolver->resolve($hidden, null, 5);
        $this->assertFalse($guest->visible);
        $this->assertNull($guest->displayUnitCents());
        $this->assertSame(9900, $guest->unitCents, 'staff still see an indicative price');

        $approved = Company::factory()->approved()->create();
        $this->assertSame(9900, $this->resolver->resolve($hidden, null, 5, $approved)->displayUnitCents());

        $por = Product::factory()->create(['price_on_request' => true, 'base_price_cents' => 1000]);
        $r = $this->resolver->resolve($por, null, 5, $approved);
        $this->assertSame(PriceSource::OnRequest, $r->source);
        $this->assertNull($r->unitCents);
    }

    public function test_moq_and_order_multiples(): void
    {
        $product = Product::factory()->create(['moq' => 12, 'order_multiple' => 6]);
        $variant = ProductVariant::factory()->for($product)->create(['moq' => 24]);

        $this->assertSame(['The minimum order quantity is 12.'], $this->resolver->resolve($product, null, 6)->errors);
        $this->assertSame(['Please order in multiples of 6.'], $this->resolver->resolve($product, null, 13)->errors);
        $this->assertTrue($this->resolver->resolve($product, null, 18)->isValidQuantity());
        $this->assertSame(['The minimum order quantity is 24.'], $this->resolver->resolve($product, $variant, 18)->errors);
        $this->assertSame(['Quantity must be at least 1.'], $this->resolver->resolve($product, null, 0)->errors);
    }

    public function test_variant_from_another_product_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->resolve($this->wallet, ProductVariant::factory()->create(), 1);
    }
}
