<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\Catalog;
use App\Livewire\Store\ProductPurchase;
use App\Models\Category;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function wallet(array $attrs = []): Product
    {
        $p = Product::factory()->create(array_merge(['name' => 'Classic Bifold Wallet', 'sku' => 'AH-WAL-001', 'base_price_cents' => 1800], $attrs));
        foreach ([1 => 1800, 25 => 1500, 100 => 1250, 250 => 1000] as $min => $cents) {
            PriceTier::create(['product_id' => $p->id, 'min_qty' => $min, 'unit_price_cents' => $cents]);
        }

        return $p;
    }

    public function test_core_pages_render(): void
    {
        $product = $this->wallet();
        foreach (['/', '/products', '/products/'.$product->slug, '/categories/'.$product->category->slug, '/quote', '/pages/about', '/pages/custom-manufacturing', '/pages/terms', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_unpublished_products_are_hidden(): void
    {
        $draft = Product::factory()->draft()->create();
        $this->get('/products/'.$draft->slug)->assertNotFound();
        Livewire::test(Catalog::class)->assertDontSee($draft->name);
        $this->actingAs($this->staff())->get('/products/'.$draft->slug)->assertOk()->assertSee('Staff preview');
    }

    public function test_catalog_search_by_name_sku_and_variant_sku(): void
    {
        $wallet = $this->wallet();
        $belt = Product::factory()->create(['name' => 'Dress Belt', 'sku' => 'AH-BLT-001']);
        ProductVariant::factory()->for($belt)->create(['sku' => 'AH-BLT-001-BLK']);

        Livewire::test(Catalog::class)->set('q', 'Bifold')->assertSee($wallet->name)->assertDontSee('Dress Belt');
        Livewire::test(Catalog::class)->set('q', 'AH-WAL')->assertSee($wallet->name)->assertDontSee('Dress Belt');
        Livewire::test(Catalog::class)->set('q', 'BLT-001-BLK')->assertSee('Dress Belt');
    }

    public function test_catalog_filters_by_category_material_color_availability_and_price(): void
    {
        $bags = Category::factory()->create(['slug' => 'bags']);
        $handbags = Category::factory()->create(['parent_id' => $bags->id]);
        $tote = Product::factory()->create(['name' => 'Tote Bag', 'category_id' => $handbags->id, 'material' => 'Nappa', 'base_price_cents' => 10000, 'availability' => 'made_to_order']);
        ProductVariant::factory()->for($tote)->create(['color' => 'Oxblood']);
        $wallet = $this->wallet(['material' => 'Full-grain', 'availability' => 'in_stock']);
        $hidden = Product::factory()->create(['name' => 'Secret Price Bag', 'public_pricing' => false, 'base_price_cents' => 500]);

        Livewire::test(Catalog::class, ['category' => $bags])->assertSee('Tote Bag')->assertDontSee($wallet->name);
        Livewire::test(Catalog::class)->set('material', ['Nappa'])->assertSee('Tote Bag')->assertDontSee($wallet->name);
        Livewire::test(Catalog::class)->set('color', ['Oxblood'])->assertSee('Tote Bag')->assertDontSee($wallet->name);
        Livewire::test(Catalog::class)->set('availability', ['in_stock'])->assertSee($wallet->name)->assertDontSee('Tote Bag');
        // Price filter only considers public list prices: the hidden-price product never matches.
        Livewire::test(Catalog::class)->set('priceMax', '20')->assertSee($wallet->name)->assertDontSee('Tote Bag')->assertDontSee('Secret Price Bag');
    }

    public function test_product_page_updates_unit_price_and_tier_on_quantity_change(): void
    {
        $wallet = $this->wallet();

        $component = Livewire::test(ProductPurchase::class, ['product' => $wallet]);
        foreach ([10 => ['$18.00', '$180.00'], 50 => ['$15.00', '$750.00'], 150 => ['$12.50', '$1,875.00'], 300 => ['$10.00', '$3,000.00']] as $qty => [$unit, $total]) {
            $component->set('quantity', $qty)->assertSeeHtml('data-testid="unit-price">'.$unit)->assertSeeHtml('data-testid="line-total">'.$total);
        }
        $component->set('quantity', 150)->assertSee('Order 100 more (250+)');
    }

    public function test_hidden_trade_prices_are_not_leaked_to_guests(): void
    {
        $bag = Product::factory()->create(['name' => 'Trade Only Bag', 'public_pricing' => false, 'base_price_cents' => 16800]);

        $this->get('/products/'.$bag->slug)->assertOk()->assertDontSee('168.00')->assertSee('Trade pricing');
        Livewire::test(ProductPurchase::class, ['product' => $bag])->assertDontSee('168.00');
        $this->get('/products')->assertDontSee('168.00');
        $this->get('/products/'.$bag->slug)->assertDontSee('"price"', false);

        $company = Company::factory()->approved()->create();
        $this->actingAs($this->customer($company));
        Livewire::test(ProductPurchase::class, ['product' => $bag])->assertSee('$168.00');
    }

    public function test_negotiated_prices_are_private_to_the_company(): void
    {
        $wallet = $this->wallet();
        $acme = Company::factory()->approved()->create(['customer_group_id' => CustomerGroup::query()->where('slug', 'vip')->value('id')]);
        CustomerPrice::create(['company_id' => $acme->id, 'product_id' => $wallet->id, 'unit_price_cents' => 1111]);

        $this->actingAs($this->customer($acme));
        Livewire::test(ProductPurchase::class, ['product' => $wallet])->set('quantity', 10)->assertSee('$11.11')->assertSee('Your negotiated price');

        $this->actingAs($this->customer(Company::factory()->approved()->create()));
        Livewire::test(ProductPurchase::class, ['product' => $wallet])->set('quantity', 10)->assertDontSee('$11.11')->assertSee('$18.00');
        $this->get('/account/pricing')->assertDontSee('11.11');
    }

    public function test_price_on_request_products_show_no_price(): void
    {
        $custom = Product::factory()->create(['name' => 'Private Label Programme', 'price_on_request' => true, 'base_price_cents' => 4242]);
        $this->get('/products/'.$custom->slug)->assertOk()->assertSee('Price on request')->assertDontSee('42.42');
    }

    public function test_product_page_has_seo_metadata(): void
    {
        $wallet = $this->wallet(['meta_description' => 'Trade wallet in full-grain leather']);
        $this->get('/products/'.$wallet->slug)
            ->assertSee('<link rel="canonical" href="'.route('products.show', $wallet).'">', false)
            ->assertSee('Trade wallet in full-grain leather')
            ->assertSee('application/ld+json', false);
    }

    public function test_search_wildcards_are_literal(): void
    {
        $this->wallet();
        Livewire::test(Catalog::class)->set('q', '%')->assertDontSee('Classic Bifold Wallet');
        Livewire::test(Catalog::class)->set('q', 'AH_WAL')->assertDontSee('Classic Bifold Wallet');
    }
}
