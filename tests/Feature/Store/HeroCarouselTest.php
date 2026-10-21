<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\SettingsForm;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HeroCarouselTest extends TestCase
{
    use RefreshDatabase;

    private function productWithImage(array $attrs, string $view = 'front'): Product
    {
        $p = Product::factory()->create($attrs);
        $p->images()->create(['disk' => 'public', 'path' => "img/{$p->id}-{$view}.webp", 'view_type' => $view, 'status' => 'approved', 'source' => 'upload', 'alt' => $p->name]);

        return $p;
    }

    public function test_carousel_shows_featured_products_then_bestsellers_with_text_and_ctas_intact(): void
    {
        $a = $this->productWithImage(['name' => 'Featured Tote', 'is_featured' => true], 'lifestyle');
        $b = $this->productWithImage(['name' => 'Featured Wallet', 'is_featured' => true]);
        $c = $this->productWithImage(['name' => 'Best Belt', 'is_bestseller' => true]);
        $this->productWithImage(['name' => 'Draft Bag', 'is_featured' => true, 'status' => 'draft']);
        $this->productWithImage(['name' => 'Plain Item']);

        $html = $this->get('/')->assertOk()
            ->assertSee('Leather goods,')->assertSee('Explore Products')->assertSee('Request a Wholesale Quote')
            ->assertSee('aria-roledescription="carousel"', false)
            ->assertSee('Show Best Belt')
            ->getContent();

        $this->assertSame(3, substr_count($html, 'aria-roledescription="slide"'));
        $this->assertStringContainsString("img/{$a->id}-lifestyle.webp", $html);
        $this->assertStringNotContainsString('Draft Bag', $html);
        $this->assertStringNotContainsString('Show Plain Item', $html);
        // Featured slides come before bestsellers
        $this->assertLessThan(strpos($html, 'Show Best Belt'), strpos($html, 'Show Featured Wallet'));
    }

    public function test_single_slide_has_no_controls_and_no_products_renders_no_carousel(): void
    {
        $this->get('/')->assertOk()->assertDontSee('aria-roledescription="carousel"', false);

        $this->productWithImage(['name' => 'Only One', 'is_featured' => true]);
        $this->get('/')->assertSee('aria-roledescription="carousel"', false)->assertDontSee('aria-label="Next product"', false);
    }

    public function test_autoplay_interval_is_configurable(): void
    {
        $this->productWithImage(['is_featured' => true]);
        $this->productWithImage(['is_featured' => true]);
        $this->get('/')->assertSee('heroCarousel(2, 6)', false);

        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)->set('s.hero_autoplay', 61)->call('save')->assertHasErrors('s.hero_autoplay')
            ->set('s.hero_autoplay', 0)->call('save')->assertHasNoErrors();
        app(Settings::class)->flush();
        $this->get('/')->assertSee('heroCarousel(2, 0)', false);
    }
}
