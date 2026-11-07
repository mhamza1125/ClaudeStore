<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __invoke()
    {
        $with = ['approvedImages', 'priceTiers', 'category'];

        return view('store.home', [
            'categories' => Category::active()->roots()->where('is_featured', true)->orderBy('sort_order')->withCount('products')->get(),
            'bestsellers' => Product::published()->where('is_bestseller', true)->with($with)->latest('published_at')->limit(4)->get(),
            'newArrivals' => Product::published()->where('is_raw_leather', false)->with($with)->latest('published_at')->limit(4)->get(),
            'rawLeather' => Product::published()->where('is_raw_leather', true)->with(['approvedImages', 'category'])
                ->orderByDesc('is_featured')->latest('published_at')->limit(5)->get(),
            'heroSlides' => HeroSlide::forHomepage(),
            'featured' => $this->featuredProducts(),
            'productsAutoplaySeconds' => (int) setting('homepage.products_autoplay_seconds', 5),
            'heroAutoplaySeconds' => (int) setting('homepage.hero_autoplay_seconds', 6),
            'testimonials' => collect(setting('homepage.testimonials', []))->filter(fn ($t) => filled($t['quote'] ?? null))->values(),
        ]);
    }

    /**
     * Featured products carousel: products marked "Featured" first (admin-controlled), topped up
     * with bestsellers, up to eight.
     *
     * @return Collection<int, Product>
     */
    private function featuredProducts(): Collection
    {
        $with = ['approvedImages', 'priceTiers', 'category'];
        $products = Product::published()->where('is_featured', true)->with($with)->orderByDesc('published_at')->limit(8)->get();
        if ($products->count() < 8) {
            $products = $products->concat(Product::published()->where('is_bestseller', true)
                ->whereNotIn('id', $products->pluck('id'))->with($with)
                ->orderByDesc('published_at')->limit(8 - $products->count())->get());
        }

        return $products->values();
    }
}
