<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRoutesTest extends TestCase
{
    use RefreshDatabase;

    private const SLUGS = ['about', 'custom-manufacturing', 'terms', 'privacy', 'shipping', 'returns'];

    public function test_informational_pages_live_at_the_root_with_canonical_urls(): void
    {
        foreach (self::SLUGS as $slug) {
            $this->assertSame(url('/'.$slug), route('pages.show', $slug));
            $this->get('/'.$slug)->assertOk()->assertSee('<link rel="canonical" href="'.url('/'.$slug).'">', false);
        }
    }

    public function test_old_pages_urls_redirect_permanently_and_keep_query_strings(): void
    {
        foreach (self::SLUGS as $slug) {
            $this->get('/pages/'.$slug)->assertStatus(301)->assertRedirect('/'.$slug);
        }
        $this->get('/pages/about?utm_source=newsletter')->assertStatus(301)->assertRedirect('/about?utm_source=newsletter');
    }

    public function test_unknown_slugs_are_not_caught(): void
    {
        $this->get('/pages/contact')->assertNotFound();
        $this->get('/pages/nonsense')->assertNotFound();
        $this->get('/contacts')->assertNotFound();
        $this->get('/nonsense')->assertNotFound();
    }

    public function test_product_category_and_app_routes_are_unaffected(): void
    {
        $product = Product::factory()->create(['slug' => 'about-wallet']);
        $category = Category::factory()->create(['slug' => 'terms']); // a category slug equal to a page slug
        $this->get('/products/about-wallet')->assertOk();
        $this->get('/categories/terms')->assertOk();
        $this->get('/products')->assertOk();
        $this->get('/quote')->assertOk();
        $this->get('/login')->assertOk();
        $this->assertSame(url('/categories/terms'), route('catalog.category', $category));
    }

    public function test_navigation_uses_the_new_urls(): void
    {
        Product::factory()->create();
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('href="'.url('/about').'"', $html);
        $this->assertStringContainsString('href="'.url('/custom-manufacturing').'"', $html);
        $this->assertStringContainsString('href="'.url('/privacy').'"', $html);
        $this->assertStringNotContainsString('/pages/', $html);
    }
}
