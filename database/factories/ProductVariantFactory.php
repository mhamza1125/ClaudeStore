<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'name' => ucfirst($this->faker->safeColorName()),
            'color' => ucfirst($this->faker->safeColorName()),
            'is_active' => true,
        ];
    }
}
