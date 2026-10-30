<?php

namespace Tests\Feature\Store;

use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    /** Covers the pricing rules for guests with B2B price access switched off. */
    protected bool $b2bPricing = false;

    public function test_suggestions_match_name_sku_and_variant_sku_like_the_catalogue(): void
    {
        $wallet = Product::factory()->create(['name' => 'Classic Bifold Wallet', 'sku' => 'AH-WAL-001', 'base_price_cents' => 1800]);
        PriceTier::create(['product_id' => $wallet->id, 'min_qty' => 100, 'unit_price_cents' => 1250]);
        $wallet->images()->create(['disk' => 'public', 'path' => 'w.webp', 'view_type' => 'front', 'status' => 'approved', 'source' => 'upload']);
        $belt = Product::factory()->create(['name' => 'Dress Belt', 'sku' => 'AH-BLT-001']);
        ProductVariant::factory()->for($belt)->create(['sku' => 'AH-BLT-001-BLK']);
        Product::factory()->draft()->create(['name' => 'Draft Wallet']);

        $this->getJson('/search/suggest?q=wallet')->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('results.0.name', 'Classic Bifold Wallet')
            ->assertJsonPath('results.0.sku', 'AH-WAL-001')
            ->assertJsonPath('results.0.url', route('products.show', $wallet))
            ->assertJsonPath('results.0.price', 'From $12.50')
            ->assertJsonPath('results.0.image', $wallet->images()->first()->url('thumb')) // signed, watermarked thumbnail
            ->assertJsonPath('all_url', route('catalog', ['q' => 'wallet']));

        $this->getJson('/search/suggest?q=blt-001-blk')->assertJsonPath('results.0.name', 'Dress Belt');
        $this->getJson('/search/suggest?q=draft')->assertJsonPath('total', 0);
    }

    public function test_exact_sku_and_prefix_matches_rank_first_and_results_are_capped(): void
    {
        Product::factory()->create(['name' => 'Zeta tote', 'sku' => 'TOTE']);
        Product::factory()->create(['name' => 'Tote classic', 'sku' => 'X-1']);
        Product::factory()->create(['name' => 'Alpha tote', 'sku' => 'X-2']);
        $names = collect($this->getJson('/search/suggest?q=tote')->json('results'))->pluck('name')->all();
        $this->assertSame(['Zeta tote', 'Tote classic', 'Alpha tote'], $names);

        Product::factory()->count(10)->create(['name' => 'Many Item']);
        $this->getJson('/search/suggest?q=many')->assertJsonCount(8, 'results')->assertJsonPath('total', 10);
    }

    public function test_short_long_and_wildcard_queries(): void
    {
        Product::factory()->create(['name' => 'Wallet']);
        $this->getJson('/search/suggest?q=w')->assertJsonPath('results', []);
        $this->getJson('/search/suggest?q='.str_repeat('a', 81))->assertJsonPath('results', []);
        $this->getJson('/search/suggest?q=%25%25')->assertJsonPath('total', 0);
    }

    public function test_hidden_and_private_prices_are_respected(): void
    {
        $trade = Product::factory()->create(['name' => 'Trade Tote', 'public_pricing' => false, 'base_price_cents' => 77777]);
        Product::factory()->create(['name' => 'Custom Tote', 'price_on_request' => true]);
        $this->getJson('/search/suggest?q=tote')->assertJsonFragment(['price' => 'Trade price'])
            ->assertJsonFragment(['price' => 'Price on request'])->assertDontSee('777.77');

        $company = Company::factory()->approved()->create();
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $trade->id, 'unit_price_cents' => 5000]);
        $this->actingAs($this->customer($company))->getJson('/search/suggest?q=trade')->assertJsonPath('results.0.price', '$50.00');
    }

    public function test_suggestions_are_rate_limited(): void
    {
        foreach (range(1, 90) as $i) {
            $this->getJson('/search/suggest?q=ab');
        }
        $this->getJson('/search/suggest?q=ab')->assertStatus(429);
    }

    public function test_header_renders_accessible_search_that_still_submits_to_the_catalogue(): void
    {
        $this->get('/')->assertSee('role="combobox"', false)->assertSee('aria-controls="header-search-listbox"', false)
            ->assertSee('action="'.route('catalog').'"', false)->assertSee('name="q"', false);
    }
}
