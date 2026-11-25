<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingAndCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_title_branding_and_contact_details(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Leamate | Leather Goods &amp; Accessories</title>', $html);
        $this->assertStringContainsString('property="og:title" content="Leamate | Leather Goods &amp; Accessories"', $html);
        $this->assertStringContainsString('images/brand/leamate-logo.png', $html);
        $this->assertStringContainsString('data-testid="contact-ribbon"', $html);
        foreach (['info@leamate.com', '(+92) 331-4657496', 'Capital Road, Sialkot, Pakistan'] as $detail) {
            $this->assertStringContainsString(e($detail), $html);
        }
        $this->assertStringNotContainsString('Atelier', $html);
        $this->assertStringNotContainsString('atelierhide', $html);
        $this->get('/contact')->assertSee('<title>Contact Us | Leamate</title>', false);
    }

    public function test_logo_files_exist(): void
    {
        $this->assertFileExists(public_path('images/brand/leamate-logo.png'));
        $this->assertFileExists(public_path('images/brand/leamate-logo@2x.png'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
    }

    public function test_category_seeder_builds_the_tree_without_duplicates_and_keeps_products(): void
    {
        Storage::fake('public');
        // Earlier demo tree with products in it.
        $bags = Category::create(['name' => 'Bags', 'slug' => 'bags', 'is_active' => true]);
        $laptop = Category::create(['name' => 'Laptop bags', 'slug' => 'laptop-bags', 'parent_id' => $bags->id, 'is_active' => true]);
        $belts = Category::create(['name' => 'Belts', 'slug' => 'belts', 'is_active' => true]);
        $belt = Product::factory()->create(['sku' => 'AH-BLT-001', 'category_id' => $belts->id, 'base_price_cents' => 2200]);
        $other = Product::factory()->create(['category_id' => $belts->id]);
        $briefcase = Product::factory()->create(['category_id' => $laptop->id]);

        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class); // idempotent

        $tree = Category::active()->roots()->orderBy('sort_order')->with('children')->get();
        $this->assertSame(['Leather Bags', 'Leather Belts', 'Leather Wallets', 'Leather Accessories'], $tree->pluck('name')->all());
        $this->assertSame(['Business Bags', 'Laptop Bags', 'Messenger Bags', 'Duffel Bags', 'Weekend & Overnight Bags'], $tree[0]->children->pluck('name')->all());
        $this->assertSame(['Cowhide Belts', 'Formal Belts', 'Casual Belts', 'Reversible Belts'], $tree[1]->children->pluck('name')->all());
        $this->assertSame(['Bifold Wallets', 'Trifold Wallets', 'Card Holders', 'Long Wallets'], $tree[2]->children->pluck('name')->all());
        $this->assertSame(['Leather Keychains', 'Leather Bracelets', 'Leather Organizers'], $tree[3]->children->pluck('name')->all());
        $this->assertSame(1, Category::query()->where('slug', 'laptop-bags')->count(), 'existing slug reused, not duplicated');
        $this->assertSame(20 + 2, Category::query()->count()); // 20 new + 2 retired (laptop-bags reused)

        // Products kept; moved into the new tree; old categories retired, not deleted.
        $this->assertSame('formal-belts', $belt->fresh()->category->slug);
        $this->assertSame(2200, $belt->fresh()->base_price_cents);
        $this->assertSame('leather-belts', $other->fresh()->category->slug);
        $this->assertSame('laptop-bags', $briefcase->fresh()->category->slug);
        $this->assertSame('leather-bags', $briefcase->fresh()->category->parent->slug);
        $this->assertFalse($bags->fresh()->is_active);
        $this->assertSame(3, Product::query()->count());
    }

    public function test_categories_show_in_navigation_and_category_pages(): void
    {
        Storage::fake('public');
        $this->seed(CategorySeeder::class);
        $belt = Product::factory()->create(['name' => 'Reversible Dress Belt', 'category_id' => Category::query()->where('slug', 'reversible-belts')->value('id')]);

        $this->get('/')->assertSee('Leather Belts')->assertSee('Reversible Belts')->assertSee(route('catalog.category', 'reversible-belts'), false);
        $this->get('/categories/leather-belts')->assertOk()->assertSee($belt->name); // parent includes subcategories
        $this->get('/categories/reversible-belts')->assertOk()->assertSee($belt->name);
        $this->get('/categories/belts')->assertNotFound(); // retired
    }

    public function test_header_uses_the_short_accessories_label_only(): void
    {
        Storage::fake('public');
        $this->seed(CategorySeeder::class);
        $html = $this->get('/')->getContent();
        $header = substr($html, 0, strpos($html, '</header>'));
        $footer = substr($html, strpos($html, '<footer'));

        $this->assertMatchesRegularExpression('~>\s*Accessories<svg~', $header, 'desktop menu');
        $this->assertStringNotContainsString('Leather Accessories', $header);
        $this->assertStringContainsString('Leather Bags', $header); // the others keep their names
        $this->assertStringContainsString('Leather Accessories', $footer);
        $this->get('/categories/leather-accessories')->assertSee('<title>Leather Accessories | Leamate</title>', false);
    }
}
