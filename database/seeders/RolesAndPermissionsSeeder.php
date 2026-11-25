<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'admin.access',
        'quotes.view-all',
        'quotes.manage',
        'quotes.assign',
        'quotes.convert',
        'orders.manage',
        'products.manage',
        'customers.view',
        'customers.manage',
        'settings.manage',
        'staff.manage',
        'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // super-admin is granted everything via Gate::before (AppServiceProvider).
        // Additive only (givePermissionTo): re-seeding never removes a permission from a role.
        Role::findOrCreate('super-admin', 'web')->givePermissionTo(self::PERMISSIONS);
        Role::findOrCreate('admin', 'web')->givePermissionTo(array_values(array_diff(self::PERMISSIONS, ['staff.manage'])));
        // Sales reps see quotes assigned to them plus unassigned ones (see Quote::scopeVisibleTo).
        Role::findOrCreate('sales-rep', 'web')->givePermissionTo([
            'admin.access', 'quotes.manage', 'quotes.convert', 'customers.view',
        ]);
        Role::findOrCreate('customer', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
