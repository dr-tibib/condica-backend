<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LeavePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'approve leave',
            'view team calendar',
            'export payroll',
            'view leave types',
            'manage leave types',
            'view public holidays',
            'manage public holidays',
            'view leave requests',
            'manage leave requests',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo([
            'approve leave',
            'view team calendar',
            'view leave requests'
        ]);

        $hrRole = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hrRole->givePermissionTo([
            'approve leave',
            'view team calendar',
            'export payroll',
            'view leave types',
            'manage leave types',
            'view public holidays',
            'manage public holidays',
            'view leave requests',
            'manage leave requests',
        ]);
    }
}
