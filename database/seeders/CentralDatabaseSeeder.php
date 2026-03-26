<?php

namespace Database\Seeders;

use App\Models\CentralUser;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the superadmin role if it doesn't exist
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // Create the manage tenants permission
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage tenants', 'guard_name' => 'web']);

        // Give manage tenants permission to superadmin role
        $superAdminRole = \Spatie\Permission\Models\Role::where(['name' => 'superadmin', 'guard_name' => 'web'])->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo('manage tenants');
        }

        // Create a global superadmin
        $admin = CentralUser::firstOrCreate(
            ['email' => 'admin@condica.com'],
            [
                'name' => 'Global Admin',
                'password' => Hash::make('password'),
                'is_global_superadmin' => true,
            ]
        );
        $admin->assignRole('superadmin');

        // Create a tenant
        $tenant = Tenant::firstOrCreate(['id' => 'test']);
        if (!$tenant->domains()->where('domain', 'localhost')->exists()) {
            $tenant->domains()->create(['domain' => 'localhost']);
        }

        // Link admin to tenant if not already linked
        if (!$tenant->users()->where('global_user_id', $admin->id)->exists()) {
            $tenant->users()->attach($admin->id);
        }
    }
}
