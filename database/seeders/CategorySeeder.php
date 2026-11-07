<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\Settings;
use Database\Seeders\Support\DemoImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The Leamate category tree. Safe to run repeatedly and on a live database:
 *
 * - categories are matched by slug and only created when missing; an existing category
 *   (and every admin edit to it: name, parent, visibility, image) is never changed;
 * - once only, products still in a category from the earlier demo tree are moved to the
 *   matching new category and those old categories are deactivated (never deleted).
 */
class CategorySeeder extends Seeder
{
    /** slug => [name, description, demo illustration shape, [child slug => name]] */
    public const TREE = [
        'leather-bags' => ['Leather Bags', 'Business, laptop, messenger, duffel and travel bags in genuine leather.', 'briefcase', [
            'business-bags' => 'Business Bags',
            'laptop-bags' => 'Laptop Bags',
            'messenger-bags' => 'Messenger Bags',
            'duffel-bags' => 'Duffel Bags',
            'weekend-overnight-bags' => 'Weekend & Overnight Bags',
        ]],
        'leather-belts' => ['Leather Belts', 'Cowhide, formal, casual and reversible belts.', 'belt', [
            'cowhide-belts' => 'Cowhide Belts',
            'formal-belts' => 'Formal Belts',
            'casual-belts' => 'Casual Belts',
            'reversible-belts' => 'Reversible Belts',
        ]],
        'leather-wallets' => ['Leather Wallets', 'Bifold, trifold and long wallets and card holders.', 'wallet', [
            'bifold-wallets' => 'Bifold Wallets',
            'trifold-wallets' => 'Trifold Wallets',
            'card-holders' => 'Card Holders',
            'long-wallets' => 'Long Wallets',
        ]],
        'leather-accessories' => ['Leather Accessories', 'Keychains, bracelets and organisers.', 'keyholder', [
            'leather-keychains' => 'Leather Keychains',
            'leather-bracelets' => 'Leather Bracelets',
            'leather-organizers' => 'Leather Organizers',
        ]],
    ];

    /** Categories of the earlier demo tree => where their products go. */
    public const LEGACY = [
        'bags' => 'leather-bags', 'handbags' => 'business-bags', 'backpacks' => 'laptop-bags', 'travel-bags' => 'duffel-bags',
        'wallets' => 'leather-wallets', 'cardholders' => 'card-holders', 'money-clips' => 'leather-wallets',
        'belts' => 'leather-belts', 'accessories' => 'leather-accessories', 'custom' => 'leather-accessories',
    ];

    /** Closer matches for individual demo products (by SKU) than their old category gives. */
    public const PRODUCT_PLACEMENT = [
        'AH-BAG-102' => 'messenger-bags',          // soft shoulder bag
        'AH-BAG-401' => 'weekend-overnight-bags',  // weekender duffel
        'AH-BLT-001' => 'formal-belts',            // dress belt
        'AH-BLT-002' => 'casual-belts',            // casual jeans belt
        'AH-ACC-001' => 'leather-keychains',       // key holder tab
        'AH-ACC-002' => 'leather-organizers',      // passport cover
        'AH-CUS-001' => 'leather-wallets',         // private-label wallet programme
        'AH-CUS-002' => 'leather-organizers',      // corporate gift set
    ];

    public function run(): void
    {
        $bySlug = $this->tree();

        // One-time move away from the earlier demo tree; never repeated, so an admin who later
        // reactivates or reuses one of those categories is not overridden.
        if (setting('seed.legacy_categories_moved')) {
            $this->command?->info('  Categories ready ('.count($bySlug).').');

            return;
        }

        $legacy = Category::query()->whereIn('slug', array_keys(self::LEGACY))->get()->keyBy('id');
        $moved = 0;
        foreach (Product::query()->whereIn('category_id', $legacy->keys())->get() as $product) {
            $target = self::PRODUCT_PLACEMENT[$product->sku] ?? self::LEGACY[$legacy[$product->category_id]->slug];
            $product->forceFill(['category_id' => $bySlug[$target]->id])->saveQuietly();
            $moved++;
        }
        Category::query()->whereKey($legacy->keys())->update(['is_active' => false, 'is_featured' => false]);
        Cache::forget('nav.categories');
        app(Settings::class)->set(['seed.legacy_categories_moved' => true]);

        $this->command?->info('  Categories ready ('.count($bySlug)."); {$moved} product(s) moved from the old tree.");
    }

    /** @return array<string, Category> */
    private function tree(): array
    {
        $out = [];
        $i = 0;
        foreach (self::TREE as $slug => [$name, $description, $shape, $children]) {
            $out[$slug] = $parent = $this->upsert($slug, ['name' => $name, 'parent_id' => null], [
                'description' => $description, 'sort_order' => $i++, 'is_featured' => true,
            ]);
            // Clearly labelled demo artwork outside production, only where no image was chosen.
            if (! app()->isProduction() && ! $parent->image_path) {
                Storage::disk('public')->put($path = "demo/categories/{$slug}.svg", DemoImage::svg($shape, '#7B3F22', 'lifestyle', $name));
                $parent->update(['image_path' => $path]);
            }

            $j = 0;
            foreach ($children as $childSlug => $childName) {
                $out[$childSlug] = $this->upsert($childSlug, ['name' => $childName, 'parent_id' => $parent->id], ['sort_order' => $j++]);
            }
        }

        return $out;
    }

    /**
     * Create-only: an existing category is returned untouched, so admin edits survive a re-run.
     * The one exception is a category still hanging under a retired demo-tree parent, which is
     * moved into the new tree (it would otherwise be unreachable). Nothing is ever deleted.
     */
    private function upsert(string $slug, array $attributes, array $onCreate): Category
    {
        $category = Category::query()->firstOrCreate(['slug' => $slug], $attributes + $onCreate + ['is_active' => true]);
        if (! $category->wasRecentlyCreated && $category->parent_id
            && Category::query()->whereKey($category->parent_id)->whereIn('slug', array_keys(self::LEGACY))->exists()) {
            $category->update($attributes + ['is_active' => true]);
        }

        return $category;
    }
}
