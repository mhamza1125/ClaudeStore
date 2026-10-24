<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Live product suggestions for the header search (same matching as the catalogue). */
class SearchController extends Controller
{
    public const LIMIT = 8;

    public function suggest(Request $request, CatalogPricing $pricing): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2 || mb_strlen($term) > 80) {
            return response()->json(['query' => $term, 'results' => [], 'total' => 0]);
        }

        $base = Product::published()->search($term);
        $total = (clone $base)->count();
        // Exact SKU, then names starting with the term, then everything else alphabetically.
        $products = $base->with(['approvedImages', 'priceTiers', 'category'])
            ->orderByRaw('CASE WHEN LOWER(sku) = ? THEN 0 WHEN LOWER(name) LIKE ? THEN 1 ELSE 2 END', [mb_strtolower($term), mb_strtolower($term).'%'])
            ->orderBy('name')
            ->limit(self::LIMIT)->get();

        $company = $request->user()?->company;

        return response()->json([
            'query' => $term,
            'total' => $total,
            'all_url' => route('catalog', ['q' => $term]),
            'results' => $products->map(function (Product $p) use ($pricing, $company) {
                $price = $pricing->summary($p, $company); // respects trade-only / on-request visibility
                $image = $p->primaryImage();

                return [
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'category' => $p->category?->name,
                    'url' => route('products.show', $p),
                    'image' => $image?->url(),
                    'price' => $price['cents'] !== null ? trim(($price['label'] === 'From' ? 'From ' : '').money($price['cents'])) : $price['label'],
                ];
            })->values(),
        ])->header('Cache-Control', 'private, max-age=30');
    }
}
