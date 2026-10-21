<?php

namespace App\Jobs;

use App\Images\Contracts\ImageProvider;
use App\Images\ImageStorage;
use App\Models\ProductImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends an original photograph to the configured provider and stores the output as a
 * NEW draft image (pending_review) linked to its source. The original is untouched.
 */
class ProcessImageWithProvider implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [30];

    public int $timeout = 120;

    public function __construct(public ProductImage $source, public ProductImage $draft) {}

    public function handle(ImageProvider $provider, ImageStorage $storage): void
    {
        $binary = $storage->originalBinary($this->source);
        if ($binary === null) {
            $this->fail('Source image missing.');

            return;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: 'image/jpeg';
        $result = $provider->removeBackground($binary, $mime);
        if (! $result->ok) {
            $this->draft->update(['status' => 'failed', 'provider_notes' => $result->error]);

            return;
        }

        // Keep the provider output as this draft's "original", then build the display copy.
        $originalPath = "product-originals/{$this->source->product_id}/".Str::uuid().'.png';
        Storage::disk(ImageStorage::ORIGINALS_DISK)->put($originalPath, $result->binary);
        [$path, $w, $h] = $storage->optimise($result->binary, $this->source->product_id);
        $this->draft->update([
            'original_disk' => ImageStorage::ORIGINALS_DISK, 'original_path' => $originalPath,
            'path' => $path, 'width' => $w, 'height' => $h,
            'status' => 'pending_review', 'provider_notes' => $result->notes,
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->draft->update(['status' => 'failed', 'provider_notes' => 'Processing failed: '.$e->getMessage()]);
    }
}
