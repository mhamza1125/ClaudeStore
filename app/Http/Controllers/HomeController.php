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
        return view('store.home', [
            'categories' => Category::active()->roots()->where('is_featured', true)->orderBy('sort_order')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])->withCount('products')->get(),
            'rawLeather' => Product::published()->where('is_raw_leather', true)->with(['approvedImages', 'category'])
                ->orderByDesc('is_featured')->latest('published_at')->limit(5)->get(),
            'heroSlides' => HeroSlide::forHomepage(),
            'productTabs' => $this->productTabs(),
            'productsAutoplaySeconds' => (int) setting('homepage.products_autoplay_seconds', 5),
            'heroAutoplaySeconds' => (int) setting('homepage.hero_autoplay_seconds', 6),
            'testimonials' => collect(setting('homepage.testimonials', []))->filter(fn ($t) => filled($t['quote'] ?? null))->values(),
        ]);
    }

    /**
     * One product section with up to three tabs. A product appears in only one tab:
     * "Featured" (products marked Featured first, topped up with bestsellers), then the
     * remaining "Bestsellers", then "New arrivals". Empty tabs are dropped; raw leather has
     * its own homepage strip.
     *
     * @return list<array{key: string, label: string, products: Collection<int, Product>}>
     */
    private function productTabs(): array
    {
        $with = ['approvedImages', 'priceTiers', 'category'];
        $base = fn () => Product::published()->where('is_raw_leather', false)->with($with);

        $featured = $base()->where('is_featured', true)->orderByDesc('published_at')->limit(8)->get();
        if ($featured->count() < 8) {
            $featured = $featured->concat($base()->where('is_bestseller', true)->whereNotIn('id', $featured->pluck('id'))
                ->orderByDesc('published_at')->limit(8 - $featured->count())->get());
        }
        $used = $featured->pluck('id');

        $bestsellers = $base()->where('is_bestseller', true)->whereNotIn('id', $used)->latest('published_at')->limit(8)->get();
        $used = $used->merge($bestsellers->pluck('id'));

        $newArrivals = $base()->whereNotIn('id', $used)->latest('published_at')->limit(8)->get();

        return collect([
            ['key' => 'featured', 'label' => 'Featured', 'products' => $featured->values()],
            ['key' => 'bestsellers', 'label' => 'Bestsellers', 'products' => $bestsellers->values()],
            ['key' => 'new', 'label' => 'New arrivals', 'products' => $newArrivals->values()],
        ])->filter(fn ($tab) => $tab['products']->isNotEmpty())->values()->all();
    }
}
