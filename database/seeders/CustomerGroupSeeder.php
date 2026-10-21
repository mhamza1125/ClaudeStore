<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['slug' => 'retail', 'name' => 'Retail buyer', 'discount_bps' => 0, 'is_default' => true, 'description' => 'Standard list and volume pricing.'],
            ['slug' => 'wholesale', 'name' => 'Wholesale buyer', 'discount_bps' => 0, 'description' => 'Approved trade accounts. Uses wholesale group tiers where configured.'],
            ['slug' => 'distributor', 'name' => 'Distributor', 'discount_bps' => 500, 'description' => 'Illustrative 5% discount on standard volume pricing.'],
            ['slug' => 'vip', 'name' => 'VIP / negotiated', 'discount_bps' => 0, 'description' => 'Accounts with negotiated, company-specific prices.'],
        ];
        foreach ($groups as $g) {
            CustomerGroup::query()->updateOrCreate(['slug' => $g['slug']], $g);
        }
    }
}
