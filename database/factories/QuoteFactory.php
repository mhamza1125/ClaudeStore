<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'AH-Q-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(900000, 999999), 6, '0', STR_PAD_LEFT),
            'status' => QuoteStatus::Pending,
            'company_name' => $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => '+1 555 0100',
            'country' => 'US',
            'delivery_destination' => 'New York, US',
            'currency' => 'USD',
            'submitted_at' => now(),
        ];
    }
}
