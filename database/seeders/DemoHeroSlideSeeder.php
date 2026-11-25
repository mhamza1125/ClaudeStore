<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\Product;
use Database\Seeders\Support\DemoImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Demonstration homepage slides with labelled illustration banners. Edit in Admin → Homepage slides. */
class DemoHeroSlideSeeder extends Seeder
{
    /** Slide definitions (shape/colour/tone also drive demo:refresh-artwork). */
    public const SLIDES = [
        [
            'shape' => 'tote', 'color' => '#7B3F22', 'tone' => 'warm', 'sku' => 'AH-BAG-101',
            'eyebrow' => 'Manufacturer · Wholesaler · Private label',
            'title' => 'Leather goods, made for trade.',
            'subtitle' => 'Bags, wallets, belts and small leather goods for retailers, distributors and brands — with transparent volume pricing and quotations within one business day.',
            'primary_label' => 'Explore Products', 'primary_url' => '/products',
            'secondary_label' => 'Request a Wholesale Quote', 'secondary_url' => '/quote',
        ],
        [
            'shape' => 'wallet', 'color' => '#A0673A', 'tone' => 'deep', 'sku' => 'AH-CUS-001',
            'eyebrow' => 'Custom manufacturing',
            'title' => 'Your brand, our workshop.',
            'subtitle' => 'Private-label programmes from pattern to packaging: logo embossing, custom colours and materials, branded hardware and retail-ready packaging.',
            'primary_label' => 'How private label works', 'primary_url' => '/custom-manufacturing',
            'secondary_label' => 'Start a custom request', 'secondary_url' => '/quote',
        ],
        [
            'shape' => 'belt', 'color' => '#1E1A18', 'tone' => 'cool', 'sku' => 'AH-BLT-001',
            'eyebrow' => 'Volume pricing',
            'title' => 'Better prices at every tier.',
            'subtitle' => 'Quantity price breaks are shown on every product and recalculated instantly — mix bags, wallets and belts in one quotation.',
            'primary_label' => 'Shop belts & wallets', 'primary_url' => '/categories/leather-wallets',
            'secondary_label' => 'See how quoting works', 'secondary_url' => '/about',
        ],
    ];

    public function run(): void
    {
        $slides = self::SLIDES;

        foreach ($slides as $i => $s) {
            // Create-only: slides that already exist (and any admin edits to them) are left alone.
            if (HeroSlide::query()->where('title', $s['title'])->exists()) {
                continue;
            }
            $path = "demo/hero/slide-{$i}.svg";
            Storage::disk('public')->put($path, DemoImage::banner($s['shape'], $s['color'], $s['title'], $s['tone']));
            HeroSlide::query()->create([
                ...collect($s)->except(['shape', 'color', 'tone', 'sku'])->all(),
                'image_path' => $path,
                'image_alt' => $s['title'].' (demo illustration)',
                'product_id' => Product::query()->where('sku', $s['sku'])->value('id'),
                'image_focus' => 'center',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }
    }
}
