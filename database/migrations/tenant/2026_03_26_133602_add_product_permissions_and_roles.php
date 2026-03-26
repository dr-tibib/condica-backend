<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'view products',
            'manage products',
            'sync products',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create product manager role if not exists
        $productManagerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'product manager', 'guard_name' => 'web']);
        $productManagerRole->givePermissionTo($permissions);

        // Update admin role to include product permissions
        $adminRole = \Spatie\Permission\Models\Role::where(['name' => 'admin', 'guard_name' => 'web'])->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        // Create the manage tenants permission (central, but might be needed in tenant for checks)
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage tenants', 'guard_name' => 'web']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'view products',
            'manage products',
            'sync products',
        ];

        foreach ($permissions as $permissionName) {
            $permission = \Spatie\Permission\Models\Permission::where(['name' => $permissionName, 'guard_name' => 'web'])->first();
            if ($permission) {
                $permission->delete();
            }
        }

        $productManagerRole = \Spatie\Permission\Models\Role::where(['name' => 'product manager', 'guard_name' => 'web'])->first();
        if ($productManagerRole) {
            $productManagerRole->delete();
        }
    }
};
