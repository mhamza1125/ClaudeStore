<?php

use App\Services\QuoteWorkflow;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('quotes:expire', function (QuoteWorkflow $workflow) {
    $count = $workflow->expireOverdue();
    $this->info("{$count} quotation(s) marked as expired.");
})->purpose('Mark sent quotations past their validity date as expired');

Schedule::command('quotes:expire')->dailyAt('00:15');
Schedule::command('queue:prune-failed --hours=720')->daily();

// Product image protection (docs/IMAGES.md). Run once after upgrading: display copies written
// to the public disk by earlier versions are moved to the private disk, so their old
// /storage URLs stop working and images are only served watermarked.
Artisan::command('images:protect', function () {
    $moved = 0;
    \App\Models\ProductImage::query()->where('disk', '!=', \App\Images\ImageStorage::DISPLAY_DISK)->whereNotNull('path')
        ->each(function (\App\Models\ProductImage $image) use (&$moved) {
            $from = \Illuminate\Support\Facades\Storage::disk($image->disk);
            $binary = $from->get($image->path);
            if ($binary === null) {
                $this->warn("Image #{$image->id}: file {$image->path} not found on the {$image->disk} disk; skipped.");

                return;
            }
            $path = \App\Images\ImageStorage::DISPLAY_DIR.'/'.($image->source === 'demo' ? 'demo' : $image->product_id).'/'.basename($image->path);
            \Illuminate\Support\Facades\Storage::disk(\App\Images\ImageStorage::DISPLAY_DISK)->put($path, $binary);
            $old = [$image->disk, $image->path];
            $image->update(['disk' => \App\Images\ImageStorage::DISPLAY_DISK, 'path' => $path]);
            \Illuminate\Support\Facades\Storage::disk($old[0])->delete($old[1]);
            $moved++;
        });
    $this->info("{$moved} product image(s) moved to private storage.");
})->purpose('Move product display images from the public disk to private storage');

Artisan::command('images:prune-cache {--days=14}', function (\App\Images\ProtectedImages $images) {
    $this->info($images->prune((int) $this->option('days')).' cached image rendition(s) removed.');
})->purpose('Delete watermarked image renditions not regenerated recently');

Schedule::command('images:prune-cache')->dailyAt('03:30');

// Live check of the chatbot's AI provider with the server's real configuration.
Artisan::command('assistant:check', function (\App\Assistant\Contracts\ChatProvider $provider) {
    $this->line('Provider: '.$provider->name().'   Model: '.($provider->model() ?? '—'));
    if (! $provider->isConfigured()) {
        $this->error('Not configured: set AI_DEFAULT_PROVIDER and the provider credentials in .env (see docs/CHATBOT.md).');

        return 1;
    }
    $started = microtime(true);
    try {
        $reply = $provider->reply('You are a connectivity check. Reply with the single word OK.', [['role' => 'user', 'content' => 'Connectivity check']]);
    } catch (\App\Assistant\AssistantUnavailable $e) {
        $this->error('Request failed: '.$e->getMessage());

        return 1;
    }
    $this->info(sprintf('Live response received in %d ms: “%s”', (microtime(true) - $started) * 1000, \Illuminate\Support\Str::limit($reply, 80)));

    return 0;
})->purpose('Send a test message to the configured chatbot AI provider');
