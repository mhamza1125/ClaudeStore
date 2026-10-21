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
 * Stores originals privately and writes optimised display copies to the public disk.
 */
class ImageStorage
{
    public const ORIGINALS_DISK = 'local';

    /** Persist an uploaded original and create the image record (optimised by a queued job). */
    public function storeUpload(Product $product, UploadedFile $file, array $attributes, string $status = 'processing'): ProductImage
    {
        $path = $file->storeAs("product-originals/{$product->id}", Str::uuid().'.'.$file->extension(), self::ORIGINALS_DISK);

        return $product->images()->create(array_merge([
            'disk' => 'public',
            'path' => null,
            'original_disk' => self::ORIGINALS_DISK,
            'original_path' => $path,
            'status' => $status,
            'source' => 'upload',
            'sort_order' => (int) $product->images()->max('sort_order') + 1,
        ], $attributes));
    }

    /** Resize/compress the original into a public WebP display image. Returns [path, width, height]. */
    public function optimise(string $binary, int $productId): array
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($binary);
        $max = (int) config('store.images.max_dimension', 2000);
        $image->scaleDown(width: $max, height: $max);
        $encoded = $image->toWebp((int) config('store.images.quality', 82));

        $path = "products/{$productId}/".Str::uuid().'.webp';
        Storage::disk('public')->put($path, (string) $encoded);

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
        $image->delete();
    }
}
