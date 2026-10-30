<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CustomerGroupSeeder::class,
            StaffSeeder::class,
            CategorySeeder::class, // the Leamate category tree (idempotent; production too)
        ]);

        if (! app()->isProduction()) {
            $this->call([
                DemoCatalogSeeder::class,
                DemoCustomerSeeder::class,
                DemoHeroSlideSeeder::class,
            ]);
        }
    }
}
