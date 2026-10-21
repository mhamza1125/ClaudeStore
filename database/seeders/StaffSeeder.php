<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Development accounts. Passwords are random per seed run and printed to the
 * console only outside production. In production set SEED_ADMIN_PASSWORD.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $production = app()->isProduction();
        if ($production && ! env('SEED_ADMIN_PASSWORD')) {
            $this->command?->warn('Skipping admin seed: set SEED_ADMIN_PASSWORD to seed an admin in production.');

            return;
        }

        $accounts = [
            ['email' => 'admin@atelierhide.test', 'name' => 'Demo Administrator', 'role' => 'super-admin'],
        ];
        if (! $production) {
            $accounts[] = ['email' => 'sales@atelierhide.test', 'name' => 'Demo Sales Rep', 'role' => 'sales-rep'];
        }

        foreach ($accounts as $a) {
            $password = $production ? env('SEED_ADMIN_PASSWORD') : Str::password(16, symbols: false);
            $user = User::query()->updateOrCreate(['email' => $a['email']], [
                'name' => $a['name'],
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $user->syncRoles([$a['role']]);
            if (! $production) {
                $this->command?->info(sprintf('  %-28s %-12s password: %s  (development only)', $a['email'], $a['role'], $password));
            }
        }
    }
}
