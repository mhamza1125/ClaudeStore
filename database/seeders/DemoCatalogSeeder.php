<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\Support\DemoImage;
use Illuminate\Database\Seeder;
use App\Images\ImageStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * DEMONSTRATION catalogue. Every product is flagged is_demo and the storefront
 * labels it as such. Prices and specifications are illustrative placeholders.
 */
class DemoCatalogSeeder extends Seeder
{
    private const COLORS = [
        'Espresso' => '#3E2619', 'Chestnut' => '#7B3F22', 'Tan' => '#A0673A', 'Cognac' => '#9A4E1F',
        'Black' => '#1E1A18', 'Oxblood' => '#5E1F1E', 'Olive' => '#5B5A3A', 'Navy' => '#23304A',
    ];

    private const DEMO_NOTE = 'This is a demonstration listing. Materials, dimensions, lead times and prices are illustrative placeholders and must be replaced with verified product data before launch.';

    public function run(): void
    {
        $cats = $this->categories();
        $wholesale = CustomerGroup::query()->where('slug', 'wholesale')->first();

        foreach ($this->products() as $data) {
            $variants = $data['variants'] ?? [];
            $tiers = $data['tiers'] ?? [];
            $groupTiers = $data['group_tiers'] ?? [];
            $shape = $data['shape'];
            unset($data['variants'], $data['tiers'], $data['group_tiers'], $data['shape']);

            $product = Product::query()->updateOrCreate(['sku' => $data['sku']], array_merge([
                'slug' => Str::slug($data['name']),
                'category_id' => $cats[$data['category']]->id,
                'description' => ($data['description'] ?? '')."\n\n".self::DEMO_NOTE,
                'status' => 'published',
                'published_at' => now()->subDays(random_int(1, 90)),
                'is_demo' => true,
                'quote_enabled' => true,
            ], collect($data)->except(['category', 'description'])->all()));

            $product->priceTiers()->delete();
            foreach ($tiers as $min => $cents) {
                PriceTier::create(['product_id' => $product->id, 'min_qty' => $min, 'unit_price_cents' => $cents]);
            }
            foreach ($groupTiers as $min => $cents) {
                PriceTier::create(['product_id' => $product->id, 'customer_group_id' => $wholesale?->id, 'min_qty' => $min, 'unit_price_cents' => $cents]);
            }

            $this->images($product, $shape, $variants);
        }

        $this->command?->info('  Demo catalogue seeded ('.Product::query()->where('is_demo', true)->count().' demo products).');
    }

    /** The Leamate tree (CategorySeeder), keyed by slug. */
    private function categories(): array
    {
        $this->callWith(CategorySeeder::class);

        return Category::query()->get()->keyBy('slug')->all();
    }

    private function images(Product $product, string $shape, array $variants): void
    {
        $product->images()->where('source', 'demo')->delete();
        $product->variants()->delete();

        $baseColor = self::COLORS[$variants[0]['color'] ?? 'Chestnut'] ?? self::COLORS['Chestnut'];
        foreach (['front', 'side', 'back', 'detail', 'lifestyle'] as $order => $view) {
            // Product images live on the private disk and are served watermarked (ProtectedImages).
            $path = ImageStorage::DISPLAY_DIR."/demo/{$product->slug}-{$view}.svg";
            Storage::disk(ImageStorage::DISPLAY_DISK)->put($path, DemoImage::svg($shape, $baseColor, $view, $product->name));
            ProductImage::create([
                'product_id' => $product->id, 'disk' => ImageStorage::DISPLAY_DISK, 'path' => $path, 'view_type' => $view,
                'alt' => "{$product->name} — {$view} view (demo illustration)", 'status' => 'approved',
                'source' => 'demo', 'sort_order' => $order,
            ]);
        }

        foreach ($variants as $k => $v) {
            $variant = $product->variants()->create(array_merge([
                'sku' => $product->sku.'-'.strtoupper(Str::substr($v['color'] ?? 'V', 0, 3)).($v['size'] ?? false ? '-'.preg_replace('/\D+/', '', $v['size']) : ''),
                'sort_order' => $k,
                'is_active' => true,
            ], $v));
            if (isset(self::COLORS[$v['color'] ?? ''])) {
                $path = ImageStorage::DISPLAY_DIR."/demo/{$product->slug}-{$variant->id}-front.svg";
                Storage::disk(ImageStorage::DISPLAY_DISK)->put($path, DemoImage::svg($shape, self::COLORS[$v['color']], 'front', "{$product->name} {$v['name']}"));
                ProductImage::create([
                    'product_id' => $product->id, 'variant_id' => $variant->id, 'disk' => ImageStorage::DISPLAY_DISK, 'path' => $path,
                    'view_type' => 'front', 'alt' => "{$product->name} in {$v['name']} (demo illustration)",
                    'status' => 'approved', 'source' => 'demo', 'sort_order' => 10 + $k,
                ]);
            }
        }
    }

    private function products(): array
    {
        $colors = fn (array $names) => array_map(fn ($n) => ['name' => $n, 'color' => $n], $names);

        return [
            [
                'name' => 'Classic Bifold Wallet', 'sku' => 'AH-WAL-001', 'category' => 'bifold-wallets', 'shape' => 'wallet',
                'short_description' => 'A slim eight-slot bifold — our illustrative example of quantity-based wholesale pricing.',
                'description' => 'Eight card slots, two slip pockets and a full-length note compartment.',
                'material' => 'Full-grain leather', 'leather_type' => 'Cowhide (demo value)', 'dimensions' => '11.5 × 9 × 1.5 cm',
                'finishing' => 'Burnished, painted edges', 'packaging' => 'Individual dust bag, 50 per carton',
                'lead_time' => 'Stock: ships in 5 business days. Custom: 4–6 weeks.',
                'base_price_cents' => 1800, 'moq' => 1, 'availability' => 'in_stock', 'stock_quantity' => 1200,
                'is_featured' => true, 'is_bestseller' => true, 'customizable' => true,
                'specifications' => [['label' => 'Card slots', 'value' => '8'], ['label' => 'Lining', 'value' => 'Unlined (demo value)']],
                'tiers' => [1 => 1800, 25 => 1500, 100 => 1250, 250 => 1000],
                'group_tiers' => [1 => 1600, 25 => 1400, 100 => 1150, 250 => 950],
                'variants' => $colors(['Espresso', 'Tan', 'Black']),
            ],
            [
                'name' => 'Slim Cardholder', 'sku' => 'AH-WAL-002', 'category' => 'card-holders', 'shape' => 'cardholder',
                'short_description' => 'Four-slot cardholder with a central pocket.',
                'material' => 'Full-grain leather', 'dimensions' => '10 × 7 cm', 'finishing' => 'Painted edges',
                'packaging' => 'Gift sleeve, 100 per carton', 'lead_time' => 'Ships in 5 business days.',
                'base_price_cents' => 1200, 'moq' => 10, 'order_multiple' => 5, 'availability' => 'in_stock', 'stock_quantity' => 2000,
                'is_bestseller' => true, 'customizable' => true,
                'tiers' => [10 => 1200, 50 => 990, 200 => 820, 500 => 700],
                'variants' => $colors(['Black', 'Cognac', 'Navy', 'Oxblood']),
            ],
            [
                'name' => 'Money Clip Wallet', 'sku' => 'AH-WAL-003', 'category' => 'leather-wallets', 'shape' => 'moneyclip',
                'short_description' => 'Card wallet with an integrated spring-steel money clip.',
                'material' => 'Top-grain leather', 'hardware' => 'Brushed steel clip (demo value)', 'dimensions' => '10.5 × 7.5 cm',
                'lead_time' => 'Ships in 7 business days.', 'base_price_cents' => 2400, 'moq' => 12,
                'availability' => 'low_stock', 'stock_quantity' => 140,
                'tiers' => [12 => 2400, 48 => 2100, 144 => 1850],
                'variants' => $colors(['Black', 'Tan']),
            ],
            [
                'name' => 'Heritage Structured Tote', 'sku' => 'AH-BAG-101', 'category' => 'business-bags', 'shape' => 'tote',
                'short_description' => 'A structured everyday tote with reinforced handles and an interior zip pocket.',
                'material' => 'Full-grain leather', 'dimensions' => '38 × 30 × 14 cm', 'hardware' => 'Antique brass (demo value)',
                'finishing' => 'Hand-stained edges', 'packaging' => 'Dust bag and tissue, 10 per carton',
                'lead_time' => 'Made to order: 3–4 weeks.', 'base_price_cents' => 14500, 'moq' => 5,
                'availability' => 'made_to_order', 'is_featured' => true, 'is_bestseller' => true, 'customizable' => true,
                'tiers' => [5 => 14500, 20 => 12800, 50 => 11200],
                'variants' => $colors(['Chestnut', 'Black', 'Tan']),
            ],
            [
                'name' => 'Soft Hobo Shoulder Bag', 'sku' => 'AH-BAG-102', 'category' => 'messenger-bags', 'shape' => 'tote',
                'short_description' => 'Slouchy hobo silhouette. Trade pricing is shown to approved wholesale accounts only.',
                'material' => 'Nappa leather', 'dimensions' => '40 × 32 × 12 cm', 'lead_time' => 'Made to order: 4 weeks.',
                'base_price_cents' => 16800, 'moq' => 5, 'public_pricing' => false, 'availability' => 'made_to_order',
                'tiers' => [5 => 16800, 25 => 14900],
                'variants' => $colors(['Oxblood', 'Black']),
            ],
            [
                'name' => 'Commuter Roll-Top Backpack', 'sku' => 'AH-BAG-201', 'category' => 'laptop-bags', 'shape' => 'backpack',
                'short_description' => 'Roll-top backpack with padded 15" sleeve.',
                'material' => 'Leather & waxed canvas', 'dimensions' => '45 × 30 × 14 cm', 'hardware' => 'Matte black buckles',
                'lead_time' => 'Ships in 10 business days.', 'base_price_cents' => 18900, 'moq' => 3,
                'availability' => 'in_stock', 'stock_quantity' => 60, 'is_featured' => true,
                'tiers' => [3 => 18900, 12 => 17200, 36 => 15800],
                'variants' => $colors(['Espresso', 'Olive']),
            ],
            [
                'name' => 'Executive Laptop Briefcase', 'sku' => 'AH-BAG-301', 'category' => 'laptop-bags', 'shape' => 'briefcase',
                'short_description' => 'Two-compartment briefcase for laptops up to 15".',
                'material' => 'Vegetable-tanned leather', 'dimensions' => '40 × 29 × 9 cm', 'hardware' => 'Brass lock (demo value)',
                'lead_time' => 'Made to order: 4–5 weeks.', 'base_price_cents' => 22500, 'moq' => 2,
                'availability' => 'made_to_order', 'customizable' => true,
                'tiers' => [2 => 22500, 10 => 20500, 30 => 18800],
                'variants' => $colors(['Chestnut', 'Black']),
            ],
            [
                'name' => 'Weekender Duffel', 'sku' => 'AH-BAG-401', 'category' => 'weekend-overnight-bags', 'shape' => 'duffel',
                'short_description' => 'A 45-litre weekender with detachable shoulder strap.',
                'material' => 'Full-grain leather', 'dimensions' => '55 × 28 × 28 cm', 'hardware' => 'Solid brass (demo value)',
                'lead_time' => 'Made to order: 5 weeks.', 'base_price_cents' => 26500, 'moq' => 2,
                'availability' => 'made_to_order', 'is_bestseller' => true,
                'tiers' => [2 => 26500, 10 => 23900, 25 => 21900],
                'variants' => $colors(['Cognac', 'Espresso']),
            ],
            [
                'name' => 'Carry-On Travel Holdall', 'sku' => 'AH-BAG-402', 'category' => 'duffel-bags', 'shape' => 'duffel',
                'short_description' => 'Cabin-size holdall with shoe compartment.',
                'material' => 'Leather & canvas', 'dimensions' => '50 × 25 × 25 cm',
                'lead_time' => 'Ships in 10 business days.', 'base_price_cents' => 21000, 'moq' => 2,
                'availability' => 'out_of_stock',
                'variants' => $colors(['Tan']),
            ],
            [
                'name' => 'Dress Belt 35mm', 'sku' => 'AH-BLT-001', 'category' => 'formal-belts', 'shape' => 'belt',
                'short_description' => 'Single-ply dress belt with a polished buckle. Sold in packs of six per size.',
                'material' => 'Full-grain leather', 'dimensions' => '35 mm width; sizes 30–44', 'hardware' => 'Polished nickel buckle (demo value)',
                'packaging' => 'Rolled in boxes of 6', 'lead_time' => 'Ships in 5 business days.',
                'base_price_cents' => 2200, 'moq' => 12, 'order_multiple' => 6, 'availability' => 'in_stock', 'stock_quantity' => 600,
                'is_featured' => true, 'is_bestseller' => true,
                'tiers' => [12 => 2200, 60 => 1900, 240 => 1600],
                'variants' => [
                    ['name' => 'Black / 30–36', 'color' => 'Black', 'size' => '30–36'],
                    ['name' => 'Black / 38–44', 'color' => 'Black', 'size' => '38–44', 'price_cents' => 2350],
                    ['name' => 'Chestnut / 30–36', 'color' => 'Chestnut', 'size' => '30–36'],
                    ['name' => 'Chestnut / 38–44', 'color' => 'Chestnut', 'size' => '38–44', 'price_cents' => 2350],
                ],
            ],
            [
                'name' => 'Casual Jeans Belt 40mm', 'sku' => 'AH-BLT-002', 'category' => 'casual-belts', 'shape' => 'belt',
                'short_description' => 'Heavy casual belt with a roller buckle.',
                'material' => 'Bridle leather', 'dimensions' => '40 mm width', 'hardware' => 'Antique brass roller buckle',
                'lead_time' => 'Ships in 7 business days.', 'base_price_cents' => 2600, 'moq' => 6, 'order_multiple' => 6,
                'availability' => 'in_stock', 'stock_quantity' => 300,
                'tiers' => [6 => 2600, 48 => 2250],
                'variants' => $colors(['Tan', 'Espresso']),
            ],
            [
                'name' => 'Key Holder Tab', 'sku' => 'AH-ACC-001', 'category' => 'leather-keychains', 'shape' => 'keyholder',
                'short_description' => 'Simple key tab — a popular promotional item. Packed in tens.',
                'material' => 'Full-grain leather', 'dimensions' => '9 × 3.5 cm', 'hardware' => 'Split ring',
                'lead_time' => 'Ships in 5 business days.', 'base_price_cents' => 600, 'moq' => 50, 'order_multiple' => 10,
                'availability' => 'in_stock', 'stock_quantity' => 5000, 'customizable' => true,
                'tiers' => [50 => 600, 200 => 480, 1000 => 390],
                'variants' => $colors(['Tan', 'Black', 'Navy']),
            ],
            [
                'name' => 'Passport Cover', 'sku' => 'AH-ACC-002', 'category' => 'leather-organizers', 'shape' => 'passport',
                'short_description' => 'Passport cover with two card slots.',
                'material' => 'Top-grain leather', 'dimensions' => '14 × 10 cm', 'lead_time' => 'Ships in 7 business days.',
                'base_price_cents' => 1900, 'moq' => 10, 'availability' => 'in_stock', 'stock_quantity' => 400, 'customizable' => true,
                'tiers' => [10 => 1900, 50 => 1650, 200 => 1400],
                'variants' => $colors(['Navy', 'Cognac']),
            ],
            [
                'name' => 'Private-Label Wallet Programme', 'sku' => 'AH-CUS-001', 'category' => 'leather-wallets', 'shape' => 'wallet',
                'short_description' => 'Your design, your branding. Pricing depends on specification and volume.',
                'description' => 'Bring a tech pack or start from one of our patterns. We quote on materials, hardware, branding method and volume.',
                'lead_time' => 'Sampling 2–3 weeks; production 6–8 weeks after sample approval.',
                'moq' => 300, 'price_on_request' => true, 'is_custom' => true, 'customizable' => true,
                'availability' => 'made_to_order', 'is_featured' => true,
            ],
            [
                'name' => 'Embossed Corporate Gift Set', 'sku' => 'AH-CUS-002', 'category' => 'leather-organizers', 'shape' => 'passport',
                'short_description' => 'Cardholder and key tab gift set with blind or foil embossing.',
                'moq' => 100, 'price_on_request' => true, 'is_custom' => true, 'customizable' => true,
                'availability' => 'made_to_order', 'lead_time' => '4–6 weeks after artwork approval.',
            ],
        ];
    }
}
