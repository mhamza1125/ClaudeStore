<?php

namespace Tests\Feature\Images;

use App\Images\ProtectedImages;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Signed, access-checked, watermarked delivery of product images. */
class ProtectedImageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->product = Product::factory()->create();
    }

    /** A uniform mid-grey photo, so any watermark shows up as changed pixels. */
    private function image(array $attrs = [], int $w = 1800, int $h = 2000): ProductImage
    {
        $gd = imagecreatetruecolor($w, $h);
        imagefill($gd, 0, 0, imagecolorallocate($gd, 120, 120, 120));
        ob_start();
        imagewebp($gd, null, 90);
        $path = 'product-display/'.$this->product->id.'/'.uniqid().'.webp';
        Storage::disk('local')->put($path, ob_get_clean());

        return $this->product->images()->create(array_merge(['disk' => 'local', 'path' => $path, 'status' => 'approved', 'source' => 'upload'], $attrs));
    }

    private function decode(string $binary): \GdImage
    {
        return imagecreatefromstring($binary);
    }

    /** Share of sampled pixels that differ from the plain grey background. */
    private function markedShare(string $binary): float
    {
        $gd = $this->decode($binary);
        [$w, $h] = [imagesx($gd), imagesy($gd)];
        $changed = $total = 0;
        for ($x = 0; $x < $w; $x += 4) {
            for ($y = 0; $y < $h; $y += 4) {
                $total++;
                $rgb = imagecolorat($gd, $x, $y);
                $changed += abs((($rgb >> 16) & 0xFF) - 120) > 6 ? 1 : 0;
            }
        }

        return $changed / $total;
    }

    public function test_urls_are_signed_and_never_expose_the_stored_file(): void
    {
        $image = $this->image();
        $url = $image->url('card');
        $this->assertStringContainsString('/media/products/'.$image->id.'/card/', $url);
        $this->assertStringNotContainsString('/storage/', $url);
        $this->assertStringNotContainsString($image->path, $url);

        $this->get($url)->assertOk();
        $this->get(str_replace('/card/', '/large/', $url))->assertNotFound(); // signature is per size
        $this->get(preg_replace('~/card/[^?]+~', '/card/'.str_repeat('a', 24), $url))->assertNotFound();
        $this->get("/media/products/{$image->id}/original/x")->assertNotFound();
    }

    public function test_renditions_are_resized_and_carry_the_company_watermark(): void
    {
        $image = $this->image();
        foreach (ProtectedImages::SIZES as $size => $max) {
            $response = $this->get($image->url($size))->assertOk()->assertHeader('Content-Type', 'image/webp');
            $gd = $this->decode($response->getContent());
            $this->assertLessThanOrEqual($max, max(imagesx($gd), imagesy($gd)), "{$size} is resized");
            $this->assertGreaterThan(0.004, $this->markedShare($response->getContent()), "{$size} is watermarked");
        }
        $this->assertStringContainsString('public', $this->get($image->url('card'))->headers->get('Cache-Control'));

        app(Settings::class)->set(['images.watermark_enabled' => false]);
        $plain = $this->get($image->url('card'))->assertOk()->getContent();
        $this->assertLessThan(0.002, $this->markedShare($plain), 'watermark can be switched off');
    }

    public function test_a_single_mark_sits_in_the_centre(): void
    {
        $gd = $this->decode($this->get($this->image()->url('card'))->getContent());
        [$w, $h] = [imagesx($gd), imagesy($gd)];
        $marked = function (int $x0, int $y0, int $x1, int $y1) use ($gd): int {
            $n = 0;
            for ($x = $x0; $x < $x1; $x += 2) {
                for ($y = $y0; $y < $y1; $y += 2) {
                    $n += abs(((imagecolorat($gd, $x, $y) >> 16) & 0xFF) - 120) > 6 ? 1 : 0;
                }
            }

            return $n;
        };
        $this->assertGreaterThan(50, $marked((int) ($w * 0.25), (int) ($h * 0.4), (int) ($w * 0.75), (int) ($h * 0.6)), 'mark in the centre');
        foreach ([[0, 0], [(int) ($w * 0.7), 0], [0, (int) ($h * 0.7)], [(int) ($w * 0.7), (int) ($h * 0.7)]] as [$x, $y]) {
            $this->assertSame(0, $marked($x, $y, $x + (int) ($w * 0.3), $y + (int) ($h * 0.3)), 'corners stay clean (no tiling)');
        }
    }

    public function test_overlay_mode_serves_unmarked_copies_and_shows_a_css_mark(): void
    {
        app(Settings::class)->set(['images.watermark_mode' => 'overlay', 'b2b.enabled' => false]);
        $image = $this->image();
        $this->assertLessThan(0.002, $this->markedShare($this->get($image->url('card'))->assertOk()->getContent()), 'file is not marked');

        $html = $this->get(route('products.show', $this->product))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="image-watermark"', $html);
        $this->assertStringContainsString('<span>'.e(setting('brand.name')).'</span>', $html);
        $this->get('/products')->assertSee('data-testid="image-watermark"', false);

        app(Settings::class)->set(['images.watermark_mode' => 'embedded']);
        $this->get(route('products.show', $this->product))->assertDontSee('data-testid="image-watermark"', false);
    }

    public function test_personalised_watermark_differs_per_customer_and_is_not_shared_cached(): void
    {
        app(Settings::class)->set(['images.watermark_personalized' => true]);
        $image = $this->image();
        $guest = $this->get($image->url('card'))->assertOk()->getContent();

        $a = User::factory()->create(['email' => 'a@shop.test'])->assignRole('customer');
        $b = User::factory()->create(['email' => 'b@shop.test'])->assignRole('customer');
        $this->actingAs($a);
        $urlA = $image->url('card');
        $responseA = $this->get($urlA)->assertOk();
        $this->assertStringContainsString('private', $responseA->headers->get('Cache-Control'));
        $this->actingAs($b);
        $urlB = $image->url('card');
        $responseB = $this->get($urlB)->assertOk();

        $this->assertNotSame($urlA, $urlB, 'URLs differ so browsers never reuse another viewer\'s copy');
        $this->assertNotSame($responseA->getContent(), $responseB->getContent());
        $this->assertNotSame($guest, $responseA->getContent());
        $this->assertGreaterThan(0.004, $this->markedShare($responseA->getContent()));
    }

    public function test_drafts_and_unpublished_products_are_only_served_to_staff(): void
    {
        $draft = $this->image(['status' => 'pending_review']);
        $this->get($draft->url('thumb'))->assertNotFound();

        $live = $this->image();
        $this->product->update(['status' => 'draft']);
        $this->get($live->url('thumb'))->assertNotFound();

        $this->actingAs($this->customer())->get($draft->url('thumb'))->assertNotFound();
        $this->actingAs($this->staff())->get($draft->url('thumb'))->assertOk();
    }

    public function test_unwatermarked_copies_are_for_staff_only(): void
    {
        $image = $this->image();
        $guestRaw = $this->get($image->adminUrl('card'))->assertOk()->getContent();
        $this->assertGreaterThan(0.004, $this->markedShare($guestRaw), '"raw" is ignored for visitors');

        $staffRaw = $this->actingAs($this->staff())->get($image->adminUrl('card'))->assertOk();
        $this->assertLessThan(0.002, $this->markedShare($staffRaw->getContent()));
        $this->assertStringContainsString('private', $staffRaw->headers->get('Cache-Control'));
    }

    public function test_renditions_are_cached_and_removed_with_the_image(): void
    {
        $image = $this->image();
        $first = $this->get($image->url('card'))->getContent();
        $this->assertCount(1, Storage::disk('local')->allFiles(ProtectedImages::CACHE_DIR."/{$image->id}"));
        $this->assertSame($first, $this->get($image->url('card'))->getContent());

        $etag = $this->get($image->url('card'))->headers->get('ETag');
        $this->get($image->url('card'), ['If-None-Match' => $etag])->assertStatus(304);

        app(\App\Images\ImageStorage::class)->delete($image);
        $this->assertSame([], Storage::disk('local')->allFiles(ProtectedImages::CACHE_DIR."/{$image->id}"));
    }

    public function test_demo_svg_images_get_the_watermark_and_a_restrictive_csp(): void
    {
        Storage::disk('local')->put('product-display/demo/x.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000"><rect width="800" height="1000"/></svg>');
        $image = $this->product->images()->create(['disk' => 'local', 'path' => 'product-display/demo/x.svg', 'status' => 'approved', 'source' => 'demo']);

        $response = $this->get($image->url('large'))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString("default-src 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="protect-wm"'), 'one centred mark');
        $this->assertStringContainsString(e(setting('brand.name')), $response->getContent());
    }

    public function test_storefront_markup_uses_protected_urls_and_deters_saving(): void
    {
        $image = $this->image();
        $html = $this->get(route('products.show', $this->product))->assertOk()->getContent();
        $this->assertStringContainsString(e($image->url('card')), $html);
        $this->assertStringContainsString('draggable="false"', $html);
        $this->assertStringContainsString('data-protected', $html);
        $this->assertStringContainsString('aria-label="Zoom image"', $html);
        $this->assertStringNotContainsString($image->path, $html);
        $this->assertStringNotContainsString('/storage/product', $html);
    }

    public function test_protect_command_moves_public_display_copies_to_private_storage(): void
    {
        Storage::disk('public')->put('products/1/old.webp', 'binary');
        $image = $this->product->images()->create(['disk' => 'public', 'path' => 'products/1/old.webp', 'status' => 'approved', 'source' => 'upload']);

        $this->artisan('images:protect')->expectsOutputToContain('1 product image(s) moved')->assertSuccessful();
        $image->refresh();
        $this->assertSame('local', $image->disk);
        Storage::disk('local')->assertExists($image->path);
        Storage::disk('public')->assertMissing('products/1/old.webp');
    }

    public function test_prune_removes_stale_renditions(): void
    {
        Storage::disk('local')->put(ProtectedImages::CACHE_DIR.'/1/old.webp', 'x');
        touch(Storage::disk('local')->path(ProtectedImages::CACHE_DIR.'/1/old.webp'), now()->subDays(30)->getTimestamp());
        Storage::disk('local')->put(ProtectedImages::CACHE_DIR.'/1/new.webp', 'x');

        $this->artisan('images:prune-cache')->expectsOutputToContain('1 cached image rendition(s) removed')->assertSuccessful();
        Storage::disk('local')->assertExists(ProtectedImages::CACHE_DIR.'/1/new.webp');
    }
}
