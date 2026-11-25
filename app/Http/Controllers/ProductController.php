<?php

namespace App\Http\Controllers;

use App\Enums\Availability;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Pricing\PriceAccess;
use App\Pricing\PriceResolver;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /** schema.org ItemAvailability for each availability (checked against schema.org on 2026-11-25). */
    public const SCHEMA_AVAILABILITY = [
        'in_stock' => 'https://schema.org/InStock',
        'low_stock' => 'https://schema.org/LimitedAvailability',
        'made_to_order' => 'https://schema.org/MadeToOrder',
        'out_of_stock' => 'https://schema.org/OutOfStock',
    ];

    public function show(Product $product, PriceResolver $resolver, PriceAccess $priceAccess)
    {
        // Unpublished products are visible to staff only (preview).
        if (! $product->isPublished() && ! auth()->user()?->can('products.manage')) {
            return $this->unavailable($product);
        }

        $product->load(['category.parent', 'priceTiers', 'activeVariants', 'approvedImages']);
        $related = Product::published()->where('category_id', $product->category_id)->whereKeyNot($product->id)
            ->with(['approvedImages', 'priceTiers', 'category'])->inRandomOrder()->limit(4)->get();

        $isFavorite = auth()->check() && auth()->user()->favoriteProducts()->whereKey($product->id)->exists();

        // Structured data: price only when visible to anonymous visitors (B2B rules included).
        $guestPrice = $resolver->resolve($product, null, max(1, $product->moq), access: $priceAccess->guest());
        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->short_description ?: $product->meta_description,
            'image' => $product->approvedImages->map->url('large')->filter()->values()->all(),
            'brand' => ['@type' => 'Brand', 'name' => setting('brand.name')],
            'offers' => $guestPrice->visible && $guestPrice->hasPrice() ? [
                '@type' => 'Offer',
                'priceCurrency' => config('store.currency'),
                'price' => \App\Support\Money::toDecimalString($guestPrice->unitCents),
                'availability' => self::SCHEMA_AVAILABILITY[$product->availability->value] ?? self::SCHEMA_AVAILABILITY[Availability::InStock->value],
                'url' => route('products.show', $product),
            ] : null,
        ]);

        return view('store.product', compact('product', 'related', 'isFavorite', 'jsonLd'));
    }

    /**
     * No product currently has this slug (route ->missing()). A former slug answers with a 301
     * straight to where the product is now, in one hop (audit F-11).
     */
    public function legacySlug(string $slug)
    {
        $redirect = ProductSlugRedirect::query()->where('old_slug', $slug)->with('product.category')->first();
        abort_unless($redirect?->product, 404);

        $product = $redirect->product;
        if ($product->isPublished()) {
            return redirect()->route('products.show', $product, 301);
        }

        return $this->unavailable($product);
    }

    /**
     * A product the public cannot see: archived after being live → 301 to its active category,
     * otherwise 410 Gone; drafts (and never-published products) → 404.
     */
    private function unavailable(Product $product)
    {
        if ($product->status !== 'archived' || ! $product->wasEverPublished()) {
            abort(404);
        }
        $category = $product->category;
        if ($category && $category->is_active) {
            return redirect()->route('catalog.category', $category, 301);
        }
        abort(410);
    }

    public function specSheet(Product $product)
    {
        abort_unless($product->isPublished() && $product->spec_sheet_path, 404);
        abort_unless(Storage::disk('public')->exists($product->spec_sheet_path), 404);

        return Storage::disk('public')->download($product->spec_sheet_path, $product->sku.'-specification.'.pathinfo($product->spec_sheet_path, PATHINFO_EXTENSION));
    }
}
