<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerGroupFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word()).' buyers';

        return ['name' => $name, 'slug' => Str::slug($name), 'discount_bps' => 0];
    }
}
