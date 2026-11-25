<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\CustomerGroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $seedRoles = true;

    /**
     * B2B price access is on by default (guests must register to see prices). Suites that
     * cover the underlying pricing rules for guests (public vs trade-only, tiers in
     * listings) set this to false to test them as they apply with B2B pricing switched off.
     */
    protected bool $b2bPricing = true;

    protected function setUp(): void
    {
        parent::setUp();
        $usesDatabase = in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this), true);
        if ($this->seedRoles && $usesDatabase) {
            $this->seed([RolesAndPermissionsSeeder::class, CustomerGroupSeeder::class]);
        }
        if (! $this->b2bPricing && $usesDatabase) {
            app(\App\Services\Settings::class)->set(['b2b.enabled' => false]);
        }
    }

    protected function staff(string $role = 'admin'): User
    {
        return User::factory()->create()->assignRole($role);
    }

    protected function customer(?Company $company = null, string $companyRole = 'owner'): User
    {
        return User::factory()->create(['company_id' => $company?->id, 'company_role' => $company ? $companyRole : null])->assignRole('customer');
    }

    /** Valid quote form payload (timing token backdated to pass the bot heuristic). */
    protected function quoteForm(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Acme Retail Ltd',
            'contact_name' => 'Jane Buyer',
            'email' => 'jane@acme.test',
            'phone' => '+44 20 7946 0000',
            'country' => 'GB',
            'delivery_destination' => 'London, UK',
            'preferred_currency' => 'USD',
            'form_started' => encrypt(now()->subMinute()->timestamp),
        ], $overrides);
    }
}
