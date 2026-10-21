<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Demo trade buyer with an approved wholesale account and one negotiated price. */
class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(['name' => 'Demo Boutique Trading Co.'], [
            'email' => 'buyer@demo-boutique.test', 'phone' => '+1 555 0100', 'country' => 'US',
            'business_type' => 'Retailer', 'wholesale_status' => 'approved', 'wholesale_reviewed_at' => now(),
            'customer_group_id' => CustomerGroup::query()->where('slug', 'wholesale')->value('id'),
            'sales_rep_id' => User::query()->where('email', 'sales@atelierhide.test')->value('id'),
            'payment_terms' => 'Net 30 (demo)',
        ]);

        Address::query()->updateOrCreate(['company_id' => $company->id, 'type' => 'shipping', 'label' => 'Warehouse'], [
            'recipient' => 'Receiving dock', 'line1' => '100 Demo Street', 'city' => 'Brooklyn', 'region' => 'NY',
            'postal_code' => '11201', 'country' => 'US', 'is_default' => true,
        ]);

        $password = Str::password(16, symbols: false);
        $user = User::query()->updateOrCreate(['email' => 'buyer@demo-boutique.test'], [
            'name' => 'Demo Buyer', 'password' => $password, 'company_id' => $company->id,
            'company_role' => 'owner', 'email_verified_at' => now(),
        ]);
        $user->syncRoles(['customer']);

        if ($tote = Product::query()->where('sku', 'AH-BAG-101')->first()) {
            CustomerPrice::query()->updateOrCreate(
                ['company_id' => $company->id, 'product_id' => $tote->id, 'variant_id' => null, 'min_qty' => 1],
                ['unit_price_cents' => 11900, 'notes' => 'Demo negotiated price'],
            );
        }

        $this->command?->info(sprintf('  %-28s %-12s password: %s  (development only)', $user->email, 'customer', $password));
    }
}
