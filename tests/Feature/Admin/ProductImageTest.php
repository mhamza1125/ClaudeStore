<?php

namespace Tests\Feature\Admin;

use App\Images\Contracts\ImageProvider;
use App\Images\Providers\NullImageProvider;
use App\Images\Providers\RemoveBgProvider;
use App\Livewire\Admin\ProductEditor;
use App\Livewire\Store\ProductPurchase;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->staff());
    }

    private function png(): string
    {
        $img = imagecreatetruecolor(400, 500);
        imagefill($img, 0, 0, imagecolorallocate($img, 120, 70, 40));
        ob_start();
        imagepng($img);

        return ob_get_clean();
    }

    public function test_upload_keeps_private_original_and_publishes_optimised_webp(): void
    {
        $product = Product::factory()->create();
        Livewire::test(ProductEditor::class, ['product' => $product])
            ->set('uploads', [UploadedFile::fake()->createWithContent('front.png', $this->png())])
            ->set('uploadViewType', 'back')
            ->call('uploadImages')->assertHasNoErrors();

        $image = ProductImage::query()->sole();
        $this->assertSame('approved', $image->status); // queue is sync in tests
        $this->assertSame('back', $image->view_type->value);
        Storage::disk('local')->assertExists($image->original_path);
        Storage::disk('public')->assertExists($image->path);
        $this->assertStringEndsWith('.webp', $image->path);
        $this->assertFalse(Storage::disk('public')->exists($image->original_path), 'original is not public');
    }

    public function test_non_images_and_tiny_images_are_rejected(): void
    {
        $product = Product::factory()->create();
        Livewire::test(ProductEditor::class, ['product' => $product])
            ->set('uploads', [UploadedFile::fake()->createWithContent('evil.png', '<?php echo 1;')])
            ->call('uploadImages')->assertHasErrors('uploads.0');
        Livewire::test(ProductEditor::class, ['product' => $product])
            ->set('uploads', [UploadedFile::fake()->image('tiny.png', 50, 50)])
            ->call('uploadImages')->assertHasErrors('uploads.0');
        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_drafts_are_not_shown_on_storefront_until_approved(): void
    {
        $product = Product::factory()->create();
        Livewire::test(ProductEditor::class, ['product' => $product])
            ->set('uploads', [UploadedFile::fake()->createWithContent('gen.png', $this->png())])
            ->set('uploadAsDraft', true)->call('uploadImages');
        $image = ProductImage::query()->sole();
        $this->assertSame('pending_review', $image->status);

        Livewire::test(ProductPurchase::class, ['product' => $product])->assertDontSee($image->url());
        Livewire::test(ProductEditor::class, ['product' => $product])->call('setImageStatus', $image->id, 'approved');
        Livewire::test(ProductPurchase::class, ['product' => $product])->assertSee($image->url());
    }

    public function test_null_provider_never_pretends_to_process(): void
    {
        $this->app->instance(ImageProvider::class, new NullImageProvider);
        $product = Product::factory()->create();
        $image = $product->images()->create(['disk' => 'public', 'path' => 'x.webp', 'view_type' => 'front', 'status' => 'approved', 'source' => 'upload']);

        Livewire::test(ProductEditor::class, ['product' => $product])
            ->assertSee('not configured (manual workflow)')->assertDontSee('Remove background')
            ->call('removeBackground', $image->id)->assertHasErrors('images');
        $this->assertDatabaseCount('product_images', 1);
    }

    public function test_removebg_provider_creates_a_draft_and_preserves_the_original(): void
    {
        Http::fake([RemoveBgProvider::ENDPOINT => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);
        $this->app->instance(ImageProvider::class, new RemoveBgProvider('test-key'));

        $product = Product::factory()->create();
        Storage::disk('local')->put('product-originals/1/o.png', $this->png());
        $source = $product->images()->create(['disk' => 'public', 'path' => 'p.webp', 'original_disk' => 'local', 'original_path' => 'product-originals/1/o.png', 'view_type' => 'front', 'status' => 'approved', 'source' => 'upload']);

        Livewire::test(ProductEditor::class, ['product' => $product])->assertSee('Remove background')->call('removeBackground', $source->id)->assertHasNoErrors();

        $draft = ProductImage::query()->where('parent_id', $source->id)->sole();
        $this->assertSame('pending_review', $draft->status);
        $this->assertSame('processed', $draft->source);
        $this->assertSame('removebg', $draft->provider);
        Storage::disk('public')->assertExists($draft->path);
        Storage::disk('local')->assertExists('product-originals/1/o.png');
        Http::assertSent(fn ($r) => $r->hasHeader('X-Api-Key', 'test-key'));
    }

    public function test_provider_errors_mark_the_draft_failed(): void
    {
        Http::fake([RemoveBgProvider::ENDPOINT => Http::response(['errors' => [['title' => 'Insufficient credits']]], 402)]);
        $this->app->instance(ImageProvider::class, new RemoveBgProvider('test-key'));
        $product = Product::factory()->create();
        Storage::disk('local')->put('o.png', $this->png());
        $source = $product->images()->create(['disk' => 'public', 'path' => 'p.webp', 'original_disk' => 'local', 'original_path' => 'o.png', 'view_type' => 'front', 'status' => 'approved', 'source' => 'upload']);

        Livewire::test(ProductEditor::class, ['product' => $product])->call('removeBackground', $source->id);
        $draft = ProductImage::query()->where('parent_id', $source->id)->sole();
        $this->assertSame('failed', $draft->status);
        $this->assertStringContainsString('Insufficient credits', $draft->provider_notes);
    }

    public function test_variant_assignment_and_delete(): void
    {
        $product = Product::factory()->create();
        $variant = \App\Models\ProductVariant::factory()->for($product)->create();
        $other = \App\Models\ProductVariant::factory()->create();
        Storage::disk('public')->put('d.webp', 'x');
        $image = $product->images()->create(['disk' => 'public', 'path' => 'd.webp', 'view_type' => 'front', 'status' => 'approved', 'source' => 'upload']);

        $c = Livewire::test(ProductEditor::class, ['product' => $product]);
        $c->call('updateImage', $image->id, 'variant_id', (string) $other->id);
        $this->assertNull($image->fresh()->variant_id, 'cannot assign a variant of another product');
        $c->call('updateImage', $image->id, 'variant_id', (string) $variant->id);
        $this->assertSame($variant->id, $image->fresh()->variant_id);
        $c->call('deleteImage', $image->id);
        $this->assertDatabaseCount('product_images', 0);
        Storage::disk('public')->assertMissing('d.webp');
    }
}
