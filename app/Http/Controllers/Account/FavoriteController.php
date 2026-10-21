<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;

class FavoriteController extends Controller
{
    public function index()
    {
        $products = auth()->user()->favoriteProducts()->published()->with(['approvedImages', 'priceTiers', 'category'])->get();

        return view('account.favorites', compact('products'));
    }

    public function store(Product $product)
    {
        abort_unless($product->isPublished(), 404);
        auth()->user()->favoriteProducts()->syncWithoutDetaching([$product->id]);

        return back()->with('status', 'Saved to your products.');
    }

    public function destroy(Product $product)
    {
        auth()->user()->favoriteProducts()->detach($product->id);

        return back()->with('status', 'Removed from saved products.');
    }
}
