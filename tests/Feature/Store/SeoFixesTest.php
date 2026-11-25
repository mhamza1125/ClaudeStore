<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\ProductEditor;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCsv;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit F-05, F-09, F-10, F-11, F-23 and the custom 404. Search-engine rules confirmed against
 * Google Search Central / schema.org on 2026-11-25 (docs/DECISIONS.md).
 */
class SeoFixesTest extends TestCase
{
    use RefreshDatabase;

    // F-05 ------------------------------------------------------------------------------

    private function categoryWith(int $count): Category
    {
        $category = Category::factory()->create(['slug' => 'wallets-test']);
        foreach (range(1, $count) as $i) {
            Product::factory()->create(['category_id' => $category->id, 'name' => sprintf('Paged Wallet %02d', $i)]);
        }

        return $category;
    }

    public function test_catalogue_pagination_uses_real_links_and_self_canonicals(): void
    {
        $this->categoryWith(14);
        $base = route('catalog.category', 'wallets-test');

        $page1 = $this->get($base)->assertOk();
        $page1->assertSee('Paged Wallet 01')->assertDontSee('Paged Wallet 13');
        $page1->assertSee('href="'.$base.'?page=2"', false);
        $page1->assertSee('<link rel="canonical" href="'.$base.'">', false);

        $page2 = $this->get($base.'?page=2')->assertOk();
        $page2->assertSee('Paged Wallet 13')->assertDontSee('Paged Wallet 01');
        $page2->assertSee('<link rel="canonical" href="'.$base.'?page=2">', false);
        $page2->assertSee('Page 2');
        $page2->assertDontSee('noindex', false);
    }

    public function test_filtered_and_sorted_pages_stay_noindex(): void
    {
        $this->categoryWith(14);
        $this->get(route('catalog.category', 'wallets-test').'?sort=name&page=2')->assertSee('noindex', false);
    }

    // F-10 ------------------------------------------------------------------------------

    public function test_quote_basket_is_noindex_and_not_blocked_in_robots(): void
    {
        $this->get('/quote')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        // A robots.txt Disallow would stop crawlers from ever seeing the noindex.
        $this->assertStringNotContainsString("Disallow: /quote\n", $this->get('/robots.txt')->getContent());
    }

    // F-11 ------------------------------------------------------------------------------

    public function test_renamed_slug_redirects_permanently_from_the_editor(): void
    {
        $product = Product::factory()->create(['slug' => 'old-wallet']);
        $this->actingAs($this->staff());
        Livewire::test(ProductEditor::class, ['product' => $product])->set('form.slug', 'new-wallet')->call('save')->assertHasNoErrors();
        auth()->logout();

        $this->get('/products/old-wallet')->assertStatus(301)->assertRedirect(route('products.show', 'new-wallet'));
        $this->get('/products/new-wallet')->assertOk();
    }

    public function test_renamed_slug_redirects_from_a_csv_import(): void
    {
        Product::factory()->create(['sku' => 'CSV-SLUG', 'slug' => 'csv-old']);
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "sku,name,slug\nCSV-SLUG,Csv product,csv-new\n");
        $this->assertSame([], app(ProductCsv::class)->import($path)['errors']);

        $this->get('/products/csv-old')->assertStatus(301)->assertRedirect(route('products.show', 'csv-new'));
    }

    public function test_redirect_follows_every_rename_in_one_hop(): void
    {
        $product = Product::factory()->create(['slug' => 'v1']);
        $product->update(['slug' => 'v2']);
        $product->update(['slug' => 'v3']);

        $this->get('/products/v1')->assertRedirect(route('products.show', 'v3'));
        $this->get('/products/v2')->assertRedirect(route('products.show', 'v3'));
    }

    public function test_archived_product_redirects_to_its_active_category_or_gone(): void
    {
        $active = Category::factory()->create(['slug' => 'belts-test', 'is_active' => true]);
        $inactive = Category::factory()->create(['is_active' => false]);
        $a = Product::factory()->create(['slug' => 'archived-belt', 'category_id' => $active->id]);
        $b = Product::factory()->create(['slug' => 'archived-orphan', 'category_id' => $inactive->id]);
        $a->update(['status' => 'archived']);
        $b->update(['status' => 'archived']);

        $this->get('/products/archived-belt')->assertStatus(301)->assertRedirect(route('catalog.category', 'belts-test'));
        $this->get('/products/archived-orphan')->assertStatus(410);

        // Old slug of an archived product: one hop straight to the category.
        $a->update(['slug' => 'archived-belt-2']);
        $this->get('/products/archived-belt')->assertRedirect(route('catalog.category', 'belts-test'));
    }

    public function test_drafts_are_never_public(): void
    {
        $draft = Product::factory()->draft()->create(['slug' => 'secret-draft']);
        $this->get('/products/secret-draft')->assertNotFound();
        $draft->update(['slug' => 'secret-draft-2']);
        $this->get('/products/secret-draft')->assertNotFound();

        // An archived product that was never published is not public either.
        $neverPublished = Product::factory()->draft()->create(['slug' => 'never-live']);
        $neverPublished->update(['status' => 'archived']);
        $this->get('/products/never-live')->assertNotFound();
    }

    // F-23 ------------------------------------------------------------------------------

    public function test_sitemap_skips_demo_products_empty_categories_and_placeholder_policies(): void
    {
        $withProducts = Category::factory()->create(['slug' => 'has-products']);
        Product::factory()->create(['category_id' => $withProducts->id, 'slug' => 'real-product']);
        Product::factory()->create(['category_id' => $withProducts->id, 'slug' => 'demo-product', 'is_demo' => true]);
        Category::factory()->create(['slug' => 'empty-category']);
        app(Settings::class)->set(['policies.terms' => 'Our real terms of trade.']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/products/real-product', $xml);
        $this->assertStringNotContainsString('/products/demo-product', $xml);
        $this->assertStringContainsString('/categories/has-products', $xml);
        $this->assertStringNotContainsString('/categories/empty-category', $xml);
        // All four policies have text here (bundled resources/policies/*.md), so all are listed and
        // indexable. A policy with no text at all would be left out and noindexed
        // (SeoController / PageController); that state needs a missing bundled file, so it is
        // not reproducible in this repository without moving files.
        foreach (['terms', 'privacy', 'shipping', 'returns'] as $policy) {
            $this->assertStringContainsString('/'.$policy.'<', $xml);
            $this->get('/'.$policy)->assertOk()->assertDontSee('<meta name="robots"', false);
        }
    }

    public function test_sitemap_cache_is_refreshed_when_content_changes(): void
    {
        $this->get('/sitemap.xml')->assertOk();
        $this->assertTrue(Cache::has('seo.sitemap'));

        Product::factory()->create(['slug' => 'brand-new-product']);
        $this->assertStringContainsString('/products/brand-new-product', $this->get('/sitemap.xml')->getContent());

        $category = Category::factory()->create(['slug' => 'late-category']);
        $this->get('/sitemap.xml');
        Product::factory()->create(['category_id' => $category->id]);
        $this->assertStringContainsString('/categories/late-category', $this->get('/sitemap.xml')->getContent());

        $this->get('/sitemap.xml');
        app(Settings::class)->set(['policies.returns' => 'Returns accepted within 14 days.']);
        $this->assertFalse(Cache::has('seo.sitemap'), 'settings changes (policies) clear the cached sitemap');
    }

    // F-09 ------------------------------------------------------------------------------

    public function test_structured_data_maps_availability_and_falls_back_to_meta_description(): void
    {
        app(Settings::class)->set(['b2b.enabled' => false]); // so the Offer (and its availability) is output
        $map = ['in_stock' => 'InStock', 'low_stock' => 'LimitedAvailability', 'made_to_order' => 'MadeToOrder', 'out_of_stock' => 'OutOfStock'];
        foreach ($map as $value => $schema) {
            $p = Product::factory()->create(['availability' => $value]);
            $this->get(route('products.show', $p))->assertSee('"availability":"https://schema.org/'.$schema.'"', false);
        }

        $p = Product::factory()->create(['short_description' => null, 'meta_description' => 'Meta fallback text']);
        $this->get(route('products.show', $p))->assertSee('"description":"Meta fallback text"', false);
    }

    // Custom 404 --------------------------------------------------------------------------

    public function test_custom_404_has_the_real_status_noindex_and_a_catalogue_link(): void
    {
        $this->get('/no-such-page-here')->assertNotFound()
            ->assertSee('noindex', false)
            ->assertSee('href="'.route('catalog').'"', false);
        $this->get('/products/no-such-product')->assertNotFound()->assertSee('href="'.route('catalog').'"', false);
    }
}
