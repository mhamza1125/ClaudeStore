<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Single light theme, self-hosted fonts and the SEO surface (sitemap, robots, noindex, structured data). */
class DesignAndSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_light_theme_with_no_switcher_anywhere(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<meta name="color-scheme" content="light">', $home);
        foreach (['data-theme', 'Colour theme', 'menuitemradio', 'ah-theme', 'class="dark'] as $needle) {
            $this->assertStringNotContainsString($needle, $home);
        }
        $admin = $this->actingAs($this->staff())->get('/admin')->assertOk()->getContent();
        $this->assertStringNotContainsString('Colour theme', $admin);
        $this->assertStringNotContainsString('data-theme', $admin);
    }

    public function test_theme_preference_endpoint_is_gone(): void
    {
        $this->actingAs($this->customer())->putJson('/preferences/theme', ['theme' => 'dark'])->assertNotFound();
    }

    public function test_fonts_are_self_hosted(): void
    {
        $this->get('/')->assertDontSee('fonts.googleapis.com', false)->assertDontSee('fonts.gstatic.com', false);
    }

    public function test_sitemap_lists_published_products_categories_and_pages_only(): void
    {
        $category = Category::factory()->create(['slug' => 'leather-belts', 'is_active' => true]);
        $live = Product::factory()->create(['category_id' => $category->id]);
        $draft = Product::factory()->create(['status' => 'draft', 'category_id' => $category->id]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<loc>'.route('products.show', $live).'</loc>', $xml);
        $this->assertStringNotContainsString(route('products.show', $draft), $xml);
        $this->assertStringContainsString('<loc>'.route('catalog.category', $category).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/custom-manufacturing').'</loc>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_robots_points_to_the_sitemap_and_keeps_private_areas_out(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Sitemap: '.route('seo.sitemap'), false)
            ->assertSee('Disallow: /admin', false)->assertSee('Disallow: /account', false)->assertSee('Disallow: /q/', false)
            ->assertDontSee("Disallow: /\n", false);
    }

    public function test_search_and_filtered_catalogue_views_are_noindex(): void
    {
        $this->get('/products')->assertOk()->assertDontSee('name="robots"', false);
        $this->get('/products?q=wallet')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/products?sort=newest')->assertOk()->assertSee('noindex, follow', false);
    }

    public function test_private_pages_are_noindex(): void
    {
        $this->get('/login')->assertOk()->assertSee('noindex, follow', false);
        $this->actingAs($this->customer())->get('/account')->assertOk()->assertSee('noindex, follow', false);
    }

    public function test_structured_data_on_home_product_and_category_pages(): void
    {
        $category = Category::factory()->create(['slug' => 'leather-wallets', 'name' => 'Leather Wallets', 'is_active' => true]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get('/')->assertSee('"@type":"Organization"', false)->assertSee('"@type":"SearchAction"', false);
        $this->get(route('products.show', $product))->assertSee('"@type":"Product"', false)->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('<meta property="og:type" content="product">', false);
        $this->get(route('catalog.category', $category))->assertSee('"@type":"BreadcrumbList"', false)->assertSee('aria-label="Breadcrumb"', false);
    }
}
