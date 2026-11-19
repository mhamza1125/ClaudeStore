<?php

namespace App\Images;

use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Protected delivery of product images (see docs/IMAGES.md):
 *
 * - URLs are HMAC-signed per image and size, so private display masters and unapproved
 *   images can't be enumerated by ID;
 * - every response is a resized rendition (never the stored master or the original),
 *   watermarked on the server with the business name and, optionally, the viewer's
 *   email or customer ID;
 * - renditions are cached on the private disk, keyed by file, size and watermark.
 */
class ProtectedImages
{
    /** Longest edge in pixels for each public rendition. */
    public const SIZES = ['thumb' => 320, 'card' => 800, 'large' => 1600];

    public const CACHE_DIR = 'image-cache';

    public const FONT = 'fonts/DejaVuSans-Bold.ttf';

    /** Bump when the rendering changes, so cached renditions and browser copies are replaced. */
    private const RENDER_VERSION = 5; // 2: single centred mark (was tiled); 3–5: redrawn demo artwork

    public function url(ProductImage $image, string $size = 'card', bool $raw = false): ?string
    {
        if (! $image->path || ! isset(self::SIZES[$size])) {
            return null;
        }

        return route('media.product-image', array_filter([
            'image' => $image->id,
            'size' => $size,
            'signature' => $this->signature($image->id, $size),
            // Cache buster: changes with the file, the watermark settings and (if personalised) the viewer.
            'v' => substr(sha1(self::RENDER_VERSION.'|'.$image->path.'|'.json_encode($raw ? null : $this->watermark(auth()->user()))), 0, 10),
            'raw' => $raw ? 1 : null,
        ]));
    }

    public function signature(int $imageId, string $size): string
    {
        return substr(hash_hmac('sha256', "product-image|{$imageId}|{$size}", (string) config('app.key')), 0, 24);
    }

    /**
     * "embedded": the mark is drawn into each served copy (survives "Save image" and screenshots
     * of the file). "overlay": copies are served unmarked and the page lays the mark over them
     * with CSS (a deterrent: it is not part of the downloaded file). Originals are never
     * modified in either mode.
     */
    public function mode(): string
    {
        return setting('images.watermark_mode') === 'overlay' ? 'overlay' : 'embedded';
    }

    /**
     * Marks drawn into served images (none in overlay mode).
     *
     * @return array{company: ?string, personal: ?string, opacity: int}
     */
    public function watermark(?User $viewer): array
    {
        $marks = $this->marks($viewer);

        return $this->mode() === 'overlay' ? ['company' => null, 'personal' => null, 'opacity' => $marks['opacity']] : $marks;
    }

    /**
     * Marks for the CSS overlay (overlay mode only), or null.
     *
     * @return ?array{company: ?string, personal: ?string, opacity: int}
     */
    public function overlay(?User $viewer): ?array
    {
        $marks = $this->marks($viewer);

        return $this->mode() === 'overlay' && ($marks['company'] !== null || $marks['personal'] !== null) ? $marks : null;
    }

    /** @return array{company: ?string, personal: ?string, opacity: int} */
    private function marks(?User $viewer): array
    {
        $personal = null;
        if ($viewer && setting('images.watermark_personalized')) {
            $personal = setting('images.watermark_identifier') === 'id' ? 'Customer #'.$viewer->id : $viewer->email;
        }

        return [
            'company' => setting('images.watermark_enabled') ? (trim((string) setting('images.watermark_text')) ?: (string) setting('brand.name')) : null,
            'personal' => $personal,
            'opacity' => max(5, min(60, (int) setting('images.watermark_opacity', 22))),
        ];
    }

    /**
     * The rendition for one viewer. $raw (staff only) skips the watermark.
     *
     * @return array{0: string, 1: string, 2: bool} [binary, mime type, personalised]
     */
    public function render(ProductImage $image, string $size, ?User $viewer, bool $raw = false): ?array
    {
        $mark = $raw ? ['company' => null, 'personal' => null, 'opacity' => 0] : $this->watermark($viewer);
        $svg = str_ends_with(strtolower((string) $image->path), '.svg');
        $mime = $svg ? 'image/svg+xml' : 'image/webp';
        $cache = Storage::disk(ImageStorage::DISPLAY_DISK);
        $cachePath = self::CACHE_DIR."/{$image->id}/".sha1(json_encode([self::RENDER_VERSION, $image->path, $size, $mark])).($svg ? '.svg' : '.webp');

        if (($binary = $cache->get($cachePath)) === null) {
            $source = Storage::disk($image->disk)->get($image->path);
            if ($source === null) {
                return null;
            }
            $binary = $svg ? $this->stampSvg($source, $mark) : $this->renderRaster($source, self::SIZES[$size], $mark);
            $cache->put($cachePath, $binary);
        }

        return [$binary, $mime, $mark['personal'] !== null];
    }

    /** Remove an image's cached renditions (on delete). */
    public function forget(ProductImage $image): void
    {
        Storage::disk(ImageStorage::DISPLAY_DISK)->deleteDirectory(self::CACHE_DIR."/{$image->id}");
    }

    /** Delete cached renditions not written for $days days. Returns the number removed. */
    public function prune(int $days): int
    {
        $disk = Storage::disk(ImageStorage::DISPLAY_DISK);
        $cutoff = Carbon::now()->subDays($days)->getTimestamp();
        $removed = 0;
        foreach ($disk->allFiles(self::CACHE_DIR) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $removed++;
            }
        }

        return $removed;
    }

    private function renderRaster(string $source, int $max, array $mark): string
    {
        $image = (new ImageManager(new Driver))->read($source);
        $image->scaleDown(width: $max, height: $max);
        if ($mark['company'] !== null || $mark['personal'] !== null) {
            $this->stamp($image->core()->native(), $mark);
        }

        return (string) $image->toWebp((int) config('store.images.quality', 82));
    }

    /**
     * One centred mark: the business name across about 40% of the width, with the viewer's
     * identifier (if personalised) in smaller type underneath. Light text with a faint dark
     * shadow stays legible on both pale and dark photographs.
     */
    private function stamp(\GdImage $gd, array $mark): void
    {
        $w = imagesx($gd);
        $h = imagesy($gd);
        $font = resource_path(self::FONT);
        $alpha = (int) round(127 - 127 * $mark['opacity'] / 100); // GD: 0 = opaque, 127 = transparent
        $light = imagecolorallocatealpha($gd, 255, 255, 255, $alpha);
        $shadow = imagecolorallocatealpha($gd, 0, 0, 0, min(127, $alpha + 25));
        imagealphablending($gd, true);

        $lines = [];
        foreach ([[$mark['company'], 0.40, 0.09], [$mark['personal'], $mark['company'] !== null ? 0.30 : 0.40, 0.045]] as [$text, $widthShare, $heightShare]) {
            if ($text === null) {
                continue;
            }
            // Measure at 100 pt, then scale to the target width (capped by a height share).
            $box = imagettfbbox(100, 0, $font, $text);
            $size = max(6, min($w * $widthShare / max(1, $box[2] - $box[0]) * 100, $h * $heightShare / max(1, $box[1] - $box[7]) * 100));
            $box = imagettfbbox($size, 0, $font, $text);
            $lines[] = [$text, $size, $box[2] - $box[0], $box[1] - $box[7], -$box[7]];
        }

        $gap = (int) round($h * 0.015);
        $total = array_sum(array_column($lines, 3)) + $gap * (count($lines) - 1);
        $top = ($h - $total) / 2;
        foreach ($lines as [$text, $size, $textWidth, $textHeight, $ascent]) {
            $x = (int) round(($w - $textWidth) / 2);
            $y = (int) round($top + $ascent);
            imagettftext($gd, $size, 0, $x + 1, $y + 1, $shadow, $font, $text);
            imagettftext($gd, $size, 0, $x, $y, $light, $font, $text);
            $top += $textHeight + $gap;
        }
    }

    /** Demo illustrations are SVG: add the same centred mark as SVG text. */
    private function stampSvg(string $svg, array $mark): string
    {
        if ($mark['company'] === null && $mark['personal'] === null) {
            return $svg;
        }
        [$vx, $vy, $vw, $vh] = preg_match('~viewBox="\s*([-\d.]+)[\s,]+([-\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)\s*"~', $svg, $m)
            ? array_map('floatval', array_slice($m, 1)) : [0, 0, 800, 1000];
        $opacity = round($mark['opacity'] / 100, 2);
        $cx = $vx + $vw / 2;
        $texts = '';
        $lines = array_values(array_filter([[$mark['company'], 0.075], [$mark['personal'], 0.038]], fn ($l) => $l[0] !== null));
        foreach ($lines as $i => [$line, $share]) {
            $y = $vy + $vh / 2 + ($i - (count($lines) - 1) / 2) * $vw * 0.08;
            $texts .= sprintf('<text x="%s" y="%s" font-size="%s">%s</text>', $cx, round($y, 1), round($vw * $share, 1), htmlspecialchars($line, ENT_XML1 | ENT_QUOTES));
        }
        $layer = '<g id="protect-wm" pointer-events="none" text-anchor="middle" dominant-baseline="middle" font-family="DejaVu Sans, Arial, sans-serif" font-weight="700" fill="#fff" fill-opacity="'.$opacity.'" stroke="#000" stroke-opacity="'.round($opacity * 0.4, 2).'" stroke-width="1">'
            .$texts.'</g>';

        return preg_replace('~</svg>\s*$~', $layer.'</svg>', $svg, 1) ?? $svg;
    }
}
