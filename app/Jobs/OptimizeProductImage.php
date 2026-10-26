<?php

namespace App\Jobs;

use App\Images\ImageStorage;
use App\Models\ProductImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class OptimizeProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60];

    /** The image may be deleted while the job waits in the queue. */
    public bool $deleteWhenMissingModels = true;

    /** @param string $statusAfter approved | pending_review */
    public function __construct(public ProductImage $image, public string $statusAfter = 'approved') {}

    public function handle(ImageStorage $storage): void
    {
        // Already handled (e.g. staff ran "Process now" while this copy waited in the queue).
        if ($this->image->status !== 'processing') {
            return;
        }
        $binary = $storage->originalBinary($this->image);
        if ($binary === null) {
            $this->image->update(['status' => 'failed', 'provider_notes' => 'Original file missing.']);

            return;
        }
        [$path, $w, $h] = $storage->optimise($binary, $this->image->product_id);
        $this->image->update(['path' => $path, 'width' => $w, 'height' => $h, 'status' => $this->statusAfter]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Image optimisation failed', ['image' => $this->image->id, 'error' => $e->getMessage()]);
        $this->image->update(['status' => 'failed', 'provider_notes' => 'Optimisation failed: '.$e->getMessage()]);
    }
}
