<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class KioskAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 'admin' role if it doesn't exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }
}
