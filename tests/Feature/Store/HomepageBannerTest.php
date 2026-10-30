<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\HeroSlideManager;
use App\Livewire\Admin\SettingsForm;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageBannerTest extends TestCase
{
    use RefreshDatabase;

    private function productWithImage(array $attrs = [], string $view = 'front'): Product
    {
        $p = Product::factory()->create($attrs);
        $p->images()->create(['disk' => 'public', 'path' => "img/{$p->id}-{$view}.webp", 'view_type' => $view, 'status' => 'approved', 'source' => 'upload', 'alt' => $p->name]);

        return $p;
    }

    private function slide(array $attrs = []): HeroSlide
    {
        return HeroSlide::create(array_merge(['title' => 'Slide', 'image_path' => 'hero/x.webp', 'is_active' => true], $attrs));
    }

    public function test_default_banner_is_shown_when_no_slides_exist(): void
    {
        $this->productWithImage(['name' => 'Featured Tote', 'is_featured' => true], 'lifestyle');
        $html = $this->get('/')->assertOk()
            ->assertSee('data-testid="hero-banner"', false)
            ->assertSee('Leather goods, made for trade.')->assertSee('Explore Products')->assertSee('Request a Wholesale Quote')
            ->getContent();
        $this->assertSame(1, substr_count($html, 'aria-roledescription="slide"'));
        $this->assertStringNotContainsString('aria-label="Next slide"', $html);
        $this->assertMatchesRegularExpression('#<h1[^>]*>Leather goods, made for trade.</h1>#', $html);
    }

    public function test_live_slides_render_in_order_with_one_h1_and_inert_inactive_slides(): void
    {
        $this->slide(['title' => 'Second', 'sort_order' => 2, 'primary_label' => 'Go', 'primary_url' => '/products']);
        $this->slide(['title' => 'First', 'sort_order' => 1, 'image_focus' => 'top']);
        $this->slide(['title' => 'Hidden', 'is_active' => false]);
        $this->slide(['title' => 'Future', 'starts_at' => now()->addDay()]);
        $this->slide(['title' => 'Expired', 'ends_at' => now()->subMinute()]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'aria-roledescription="slide"'));
        $this->assertLessThan(strpos($html, '>Second<'), strpos($html, '>First<'));
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('object-position: top', $html);
        $this->assertStringContainsString('href="/products" class="btn btn-primary sm:px-7 sm:py-3.5 sm:text-base">Go', $html);
        // Inactive slides are inert (no focusable hidden links); the first is not.
        $this->assertMatchesRegularExpression('#<div class="hero-slide[^>]*\sinert\s[^>]*data-slide="1"#', $html);
        $this->assertDoesNotMatchRegularExpression('#<div class="hero-slide[^>]*\sinert\s[^>]*data-slide="0"#', $html);
        $this->assertStringContainsString('aria-label="Next slide"', $html);
        foreach (['Hidden', 'Future', 'Expired'] as $t) {
            $this->assertStringNotContainsString(">{$t}<", $html);
        }
    }

    public function test_slide_can_use_a_linked_product_photo(): void
    {
        $p = $this->productWithImage(['name' => 'Weekender'], 'lifestyle');
        $this->slide(['title' => 'Travel', 'image_path' => null, 'product_id' => $p->id]);
        $this->get('/')->assertSee($p->images()->first()->url('large'), false)->assertSee(route('products.show', $p), false);
    }

    public function test_admin_manages_slides(): void
    {
        Storage::fake('public');
        $this->actingAs($this->staff());
        $this->get('/admin/homepage-slides')->assertOk();

        Livewire::test(HeroSlideManager::class)
            ->set('form.title', 'Autumn trade show')
            ->call('save')->assertHasErrors('image')
            ->set('form.primary_url', 'javascript:alert(1)')->set('form.primary_label', 'Book')
            ->call('save')->assertHasErrors('form.primary_url')
            ->set('form.primary_url', '/quote')
            ->set('image', UploadedFile::fake()->image('banner.jpg', 1600, 1200))
            ->set('form.ends_at', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')->assertHasNoErrors();

        $slide = HeroSlide::query()->sole();
        $this->assertStringStartsWith('hero/', $slide->image_path);
        $this->assertStringEndsWith('.webp', $slide->image_path);
        Storage::disk('public')->assertExists($slide->image_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'hero_slide.created']);
        $this->get('/')->assertSee('Autumn trade show');

        $second = $this->slide(['title' => 'B', 'sort_order' => 5]);
        Livewire::test(HeroSlideManager::class)->call('move', $second->id, -1)->call('toggle', $slide->id);
        $this->assertSame(['B', 'Autumn trade show'], HeroSlide::query()->orderBy('sort_order')->pluck('title')->all());
        $this->assertFalse($slide->fresh()->is_active);

        Livewire::test(HeroSlideManager::class)->call('delete', $slide->id);
        Storage::disk('public')->assertMissing($slide->image_path);
    }

    public function test_small_images_are_rejected_and_sales_reps_cannot_edit(): void
    {
        Storage::fake('public');
        $this->actingAs($this->staff());
        Livewire::test(HeroSlideManager::class)->set('form.title', 'X')->set('image', UploadedFile::fake()->image('tiny.jpg', 300, 200))
            ->call('save')->assertHasErrors('image');
        $this->actingAs($this->staff('sales-rep'))->get('/admin/homepage-slides')->assertForbidden();
    }

    public function test_featured_carousel_lists_featured_then_bestsellers(): void
    {
        $this->productWithImage(['name' => 'Feat One', 'is_featured' => true]);
        $this->productWithImage(['name' => 'Best One', 'is_bestseller' => true]);
        $this->productWithImage(['name' => 'Draft Feat', 'is_featured' => true, 'status' => 'draft']);
        $html = $this->get('/')->assertSee('data-testid="featured-carousel"', false)->assertSee('productCarousel(5)', false)->getContent();
        $section = substr($html, strpos($html, 'data-testid="featured-carousel"'));
        $this->assertSame(2, substr_count(substr($section, 0, strpos($section, '</section>')), 'data-card'));
        $this->assertLessThan(strpos($section, 'Best One'), strpos($section, 'Feat One'));
        $this->assertStringNotContainsString('Draft Feat', $section);
    }

    public function test_autoplay_intervals_are_configurable(): void
    {
        $this->slide(['title' => 'A']);
        $this->slide(['title' => 'B']);
        $this->productWithImage(['is_featured' => true]);
        $this->get('/')->assertSee('heroBanner(2, 6)', false)->assertSee('productCarousel(5)', false);

        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)->set('s.hero_autoplay', 61)->call('save')->assertHasErrors('s.hero_autoplay')
            ->set('s.hero_autoplay', 8)->set('s.products_autoplay', 0)->call('save')->assertHasNoErrors();
        app(Settings::class)->flush();
        $this->get('/')->assertSee('heroBanner(2, 8)', false)->assertSee('productCarousel(0)', false);
    }
}
