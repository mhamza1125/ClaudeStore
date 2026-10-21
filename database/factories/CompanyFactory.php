<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'country' => 'US',
            'wholesale_status' => 'none',
        ];
    }

    public function approved(): static
    {
        return $this->state(['wholesale_status' => 'approved', 'wholesale_reviewed_at' => now()]);
    }
}
