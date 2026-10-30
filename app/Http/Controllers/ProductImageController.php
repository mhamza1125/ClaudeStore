<?php

namespace App\Http\Controllers;

use App\Images\ProtectedImages;
use App\Models\ProductImage;
use Illuminate\Http\Request;

/** Serves watermarked product image renditions; see App\Images\ProtectedImages. */
class ProductImageController extends Controller
{
    public function show(Request $request, ProductImage $image, string $size, string $signature, ProtectedImages $images)
    {
        abort_unless(hash_equals($images->signature($image->id, $size), $signature), 404);

        // Drafts, rejected images and unpublished products are visible to catalogue staff only.
        $staff = (bool) $request->user()?->can('products.manage');
        abort_unless($staff || ($image->status === 'approved' && $image->product?->isPublished()), 404);

        $raw = $staff && $request->boolean('raw');
        $rendition = $images->render($image, $size, $request->user(), $raw);
        abort_if($rendition === null, 404);
        [$binary, $mime, $personal] = $rendition;

        $response = response($binary, 200, [
            'Content-Type' => $mime,
            // Personalised and staff renditions must not be stored by shared caches.
            'Cache-Control' => ($personal || $raw ? 'private' : 'public').', max-age=86400',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        if ($mime === 'image/svg+xml') {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        }
        $response->setEtag(sha1($binary));
        $response->isNotModified($request);

        return $response;
    }
}
