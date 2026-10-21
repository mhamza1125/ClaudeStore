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
            'hero' => Product::published()->where('is_featured', true)->with('approvedImages')->first(),
            'testimonials' => collect(setting('homepage.testimonials', []))->filter(fn ($t) => filled($t['quote'] ?? null))->values(),
        ]);
    }
}
