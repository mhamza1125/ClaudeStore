<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $with = ['approvedImages', 'priceTiers', 'category'];

        return view('store.home', [
            'categories' => Category::active()->roots()->where('is_featured', true)->orderBy('sort_order')->withCount('products')->get(),
            'bestsellers' => Product::published()->where('is_bestseller', true)->with($with)->latest('published_at')->limit(4)->get(),
            'newArrivals' => Product::published()->with($with)->latest('published_at')->limit(4)->get(),
            'heroSlides' => $this->heroSlides(),
            'heroAutoplaySeconds' => (int) setting('homepage.hero_autoplay_seconds', 6),
            'testimonials' => collect(setting('homepage.testimonials', []))->filter(fn ($t) => filled($t['quote'] ?? null))->values(),
        ]);
    }

    /**
     * Hero carousel slides: featured products first (admin-controlled via the
     * product "Featured" flag), topped up with bestsellers, max five. Prefers each
     * product's lifestyle image, then its first approved image.
     *
     * @return \Illuminate\Support\Collection<int, array{product: Product, image: \App\Models\ProductImage}>
     */
    private function heroSlides(): \Illuminate\Support\Collection
    {
        $products = Product::published()->where('is_featured', true)->with('approvedImages')
            ->orderByDesc('published_at')->limit(5)->get();
        if ($products->count() < 5) {
            $products = $products->concat(Product::published()->where('is_bestseller', true)
                ->whereNotIn('id', $products->pluck('id'))->with('approvedImages')
                ->orderByDesc('published_at')->limit(5 - $products->count())->get());
        }

        return $products->map(function (Product $product) {
            $images = $product->approvedImages->whereNull('variant_id');
            $image = $images->firstWhere('view_type.value', 'lifestyle') ?? $images->first() ?? $product->approvedImages->first();

            return $image ? ['product' => $product, 'image' => $image] : null;
        })->filter()->values();
    }
}
