<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Tenant\LeavePermissionSeeder;
use Database\Seeders\Tenant\LeaveManagementSeeder;
use Database\Seeders\Tenant\KioskAdminRoleSeeder;
use Database\Seeders\Tenant\TenantSettingsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if we are in a tenant context
        $isTenant = false;
        if (function_exists('tenant') && tenant()) {
            $isTenant = true;
        }

        if ($isTenant) {
            $this->runTenantSeeders();
        } else {
            $this->runCentralSeeders();
        }
    }

    private function runTenantSeeders(): void
    {
        $this->call([
            LeavePermissionSeeder::class,
            LeaveManagementSeeder::class,
            KioskAdminRoleSeeder::class,
            TenantSettingsSeeder::class,
        ]);
    }

    private function runCentralSeeders(): void
    {
        // Central seeders will be empty for now
    }
}
