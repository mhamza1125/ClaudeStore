<?php

namespace App\Images;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Stores originals privately and writes optimised display masters to the private disk too.
 * Visitors only ever receive resized, watermarked renditions through the signed media
 * route (ProtectedImages); neither file has a public URL.
 */
class ImageStorage
{
    public const ORIGINALS_DISK = 'local';

    public const DISPLAY_DISK = 'local';

    public const DISPLAY_DIR = 'product-display';

    /** Persist an uploaded original and create the image record (optimised by a queued job). */
    public function storeUpload(Product $product, UploadedFile $file, array $attributes, string $status = 'processing'): ProductImage
    {
        $path = $file->storeAs("product-originals/{$product->id}", Str::uuid().'.'.$file->extension(), self::ORIGINALS_DISK);

        return $product->images()->create(array_merge([
            'disk' => self::DISPLAY_DISK,
            'path' => null,
            'original_disk' => self::ORIGINALS_DISK,
            'original_path' => $path,
            'status' => $status,
            'source' => 'upload',
            'sort_order' => (int) $product->images()->max('sort_order') + 1,
        ], $attributes));
    }

    /** Resize/compress the original into the private WebP display master. Returns [path, width, height]. */
    public function optimise(string $binary, int $productId): array
    {
        return $this->optimiseInto($binary, self::DISPLAY_DIR."/{$productId}", self::DISPLAY_DISK);
    }

    /** Resize (never upscale) and encode as WebP. Returns [path, width, height]. */
    public function optimiseInto(string $binary, string $directory, string $disk = 'public'): array
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($binary);
        $max = (int) config('store.images.max_dimension', 2000);
        $image->scaleDown(width: $max, height: $max);
        $encoded = $image->toWebp((int) config('store.images.quality', 82));

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk($disk)->put($path, (string) $encoded);

        return [$path, $image->width(), $image->height()];
    }

    public function originalBinary(ProductImage $image): ?string
    {
        if ($image->original_path && Storage::disk($image->original_disk)->exists($image->original_path)) {
            return Storage::disk($image->original_disk)->get($image->original_path);
        }
        if ($image->path && Storage::disk($image->disk)->exists($image->path)) {
            return Storage::disk($image->disk)->get($image->path);
        }

        return null;
    }

    public function delete(ProductImage $image): void
    {
        if ($image->path && $image->source !== 'demo') {
            Storage::disk($image->disk)->delete($image->path);
        }
        // Each non-demo image owns its original file (uploads and provider outputs).
        if ($image->original_path && $image->source !== 'demo') {
            Storage::disk($image->original_disk)->delete($image->original_path);
        }
        app(ProtectedImages::class)->forget($image);
        $image->delete();
    }
}
