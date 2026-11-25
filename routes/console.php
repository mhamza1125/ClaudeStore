<?php

use App\Assistant\AssistantUnavailable;
use App\Assistant\Contracts\ChatProvider;
use App\Images\ImageStorage;
use App\Images\ProtectedImages;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\ProductImage;
use App\Rules\TrustedEmail;
use App\Services\QuoteWorkflow;
use App\Services\SystemHealth;
use App\Support\BusinessTime;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoHeroSlideSeeder;
use Database\Seeders\Support\DemoImage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('quotes:expire', function (QuoteWorkflow $workflow) {
    $count = $workflow->expireOverdue();
    $this->info("{$count} quotation(s) marked as expired.");
})->purpose('Mark sent quotations past their validity date as expired');

// 00:15 on the business calendar (Asia/Karachi), right after a validity date ends there (audit F-13).
Schedule::command('quotes:expire')->dailyAt('00:15')->timezone(BusinessTime::zone());

// Heartbeat for the admin health warning (audit F-06): proves the cron job runs schedule:run.
Schedule::call(fn () => SystemHealth::beat())->everyMinute()->name('scheduler-heartbeat');

// Product image protection (docs/IMAGES.md). Run once after upgrading: display copies written
// to the public disk by earlier versions are moved to the private disk, so their old
// /storage URLs stop working and images are only served watermarked.
Artisan::command('images:protect', function () {
    $moved = 0;
    ProductImage::query()->where('disk', '!=', ImageStorage::DISPLAY_DISK)->whereNotNull('path')
        ->each(function (ProductImage $image) use (&$moved) {
            $from = Storage::disk($image->disk);
            $binary = $from->get($image->path);
            if ($binary === null) {
                $this->warn("Image #{$image->id}: file {$image->path} not found on the {$image->disk} disk; skipped.");

                return;
            }
            $path = ImageStorage::DISPLAY_DIR.'/'.($image->source === 'demo' ? 'demo' : $image->product_id).'/'.basename($image->path);
            Storage::disk(ImageStorage::DISPLAY_DISK)->put($path, $binary);
            $old = [$image->disk, $image->path];
            $image->update(['disk' => ImageStorage::DISPLAY_DISK, 'path' => $path]);
            Storage::disk($old[0])->delete($old[1]);
            $moved++;
        });
    $this->info("{$moved} product image(s) moved to private storage.");
})->purpose('Move product display images from the public disk to private storage');

Artisan::command('images:prune-cache {--days=14}', function (ProtectedImages $images) {
    $this->info($images->prune((int) $this->option('days')).' cached image rendition(s) removed.');
})->purpose('Delete watermarked image renditions not regenerated recently');

Schedule::command('images:prune-cache')->dailyAt('03:30');

// Live check of the chatbot's AI provider with the server's real configuration.
Artisan::command('assistant:check', function (ChatProvider $provider) {
    $this->line('Provider: '.$provider->name().'   Model: '.($provider->model() ?? '—'));
    if (! $provider->isConfigured()) {
        $this->error('Not configured: set AI_DEFAULT_PROVIDER and the provider credentials in .env (see docs/CHATBOT.md).');

        return 1;
    }
    $started = microtime(true);
    try {
        $reply = $provider->reply('You are a connectivity check. Reply with the single word OK.', [['role' => 'user', 'content' => 'Connectivity check']]);
    } catch (AssistantUnavailable $e) {
        $this->error('Request failed: '.$e->getMessage());

        return 1;
    }
    $this->info(sprintf('Live response received in %d ms: “%s”', (microtime(true) - $started) * 1000, Str::limit($reply, 80)));

    return 0;
})->purpose('Send a test message to the configured chatbot AI provider');

// Disposable-email blocklist (App\Rules\TrustedEmail). The bundled copy lives in
// resources/data; this refreshes it from the community-maintained list (CC0) into storage.
Artisan::command('emails:update-blocklist', function () {
    $url = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/main/disposable_email_blocklist.conf';
    $response = Http::timeout(30)->get($url);
    $domains = collect(preg_split('/\R/', (string) $response->body()))->map(fn ($d) => strtolower(trim($d)))
        ->filter(fn ($d) => $d !== '' && ! str_starts_with($d, '#') && str_contains($d, '.'))->unique()->values();
    if (! $response->successful() || $domains->count() < 1000) {
        $this->error('Download failed or the list looks incomplete; the current list was kept.');

        return 1;
    }
    file_put_contents(storage_path(TrustedEmail::UPDATED_LIST), $domains->implode("\n")."\n");
    TrustedEmail::flushBlocklist();
    $this->info("{$domains->count()} disposable domains saved.");

    return 0;
})->purpose('Refresh the list of disposable / temporary email domains');

Schedule::command('emails:update-blocklist')->weekly();

// Re-draw the demo illustrations in the current art direction (database/seeders/Support/DemoImage.php).
// Files only: it rewrites existing demo SVGs at their current paths and never creates, edits or
// deletes database rows. Uploaded (non-demo) images are never touched.
Artisan::command('demo:refresh-artwork {--force : Run in production}', function () {
    if (app()->isProduction() && ! $this->option('force')) {
        $this->error('Refusing to run in production without --force.');

        return 1;
    }
    $demo = DemoImage::class;
    $colors = DemoCatalogSeeder::COLORS;
    $shapes = collect(DemoCatalogSeeder::products())->pluck('shape', 'sku');
    $rootShape = fn (?string $slug) => CategorySeeder::TREE[$slug][2] ?? null;
    $written = ['products' => 0, 'categories' => 0, 'slides' => 0];

    ProductImage::query()->where('source', 'demo')->where('path', 'like', '%.svg')->with('product.category.parent', 'product.variants', 'variant')
        ->each(function (ProductImage $image) use ($demo, $colors, $shapes, $rootShape, &$written) {
            $product = $image->product;
            $disk = Storage::disk($image->disk);
            if (! $product || ! $disk->exists($image->path)) {
                return;
            }
            $root = $product->category?->parent?->slug ?? $product->category?->slug;
            $shape = $shapes[$product->sku] ?? $rootShape($root) ?? 'tote';
            $colorName = $image->variant?->color ?? $product->variants->sortBy('sort_order')->first()?->color;
            $color = $colors[$colorName] ?? $colors['Chestnut'];
            $view = $image->view_type?->value ?? 'front';
            $title = $product->name.($image->variant ? ' '.$image->variant->name : '');
            $disk->put($image->path, $demo::svg($shape, $color, in_array($view, ['front', 'side', 'back', 'detail', 'lifestyle'], true) ? $view : 'front', $title));
            $written['products']++;
        });

    Category::query()->where('image_path', 'like', 'demo/categories/%.svg')->each(function ($category) use ($demo, $rootShape, &$written) {
        if (! Storage::disk('public')->exists($category->image_path)) {
            return;
        }
        $slug = basename($category->image_path, '.svg');
        Storage::disk('public')->put($category->image_path, $demo::forCategory($slug, $rootShape($slug) ?? 'tote', $category->name));
        $written['categories']++;
    });

    HeroSlide::query()->where('image_path', 'like', 'demo/hero/slide-%.svg')->each(function ($slide) use ($demo, &$written) {
        $i = (int) preg_replace('/\D+/', '', basename($slide->image_path, '.svg'));
        $def = DemoHeroSlideSeeder::SLIDES[$i] ?? null;
        if (! $def || ! Storage::disk('public')->exists($slide->image_path)) {
            return;
        }
        Storage::disk('public')->put($slide->image_path, $demo::banner($def['shape'], $def['color'], $slide->title, $def['tone']));
        $written['slides']++;
    });

    $this->info("Redrawn: {$written['products']} product, {$written['categories']} category and {$written['slides']} banner illustration(s). No database rows were changed.");
})->purpose('Redraw demo illustrations in the current art direction (files only)');
