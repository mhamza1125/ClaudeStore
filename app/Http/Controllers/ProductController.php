<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Pricing\PriceAccess;
use App\Pricing\PriceResolver;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function show(Product $product, PriceResolver $resolver, PriceAccess $priceAccess)
    {
        // Unpublished products are visible to staff only (preview).
        if (! $product->isPublished() && ! auth()->user()?->can('products.manage')) {
            abort(404);
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
            'description' => $product->short_description,
            'image' => $product->approvedImages->map->url('large')->filter()->values()->all(),
            'brand' => ['@type' => 'Brand', 'name' => setting('brand.name')],
            'offers' => $guestPrice->visible && $guestPrice->hasPrice() ? [
                '@type' => 'Offer',
                'priceCurrency' => config('store.currency'),
                'price' => \App\Support\Money::toDecimalString($guestPrice->unitCents),
                'availability' => $product->availability->value === 'out_of_stock' ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
                'url' => route('products.show', $product),
            ] : null,
        ]);

        return view('store.product', compact('product', 'related', 'isFavorite', 'jsonLd'));
    }

    public function specSheet(Product $product)
    {
        abort_unless($product->isPublished() && $product->spec_sheet_path, 404);
        abort_unless(Storage::disk('public')->exists($product->spec_sheet_path), 404);

        return Storage::disk('public')->download($product->spec_sheet_path, $product->sku.'-specification.'.pathinfo($product->spec_sheet_path, PATHINFO_EXTENSION));
    }
}
