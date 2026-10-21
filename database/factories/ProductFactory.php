<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Test '.ucfirst($this->faker->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'sku' => 'TST-'.Str::upper(Str::random(8)),
            'short_description' => $this->faker->sentence(),
            'base_price_cents' => 2000,
            'moq' => 1,
            'order_multiple' => 1,
            'status' => 'published',
            'quote_enabled' => true,
            'public_pricing' => true,
            'price_on_request' => false,
            'availability' => 'in_stock',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }
}
