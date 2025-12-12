<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\CentralUser;
use App\Models\Tenant;

class SyncNewSuperAdminToAllTenants
{
    public function saved(CentralUser $user): void
    {
        // Check if role matches column
        $hasRole = $user->hasRole('superadmin');
        $isGlobal = (bool) $user->is_global_superadmin;

        if ($hasRole !== $isGlobal) {
            \Illuminate\Support\Facades\Log::info("SyncObserver: updating is_global_superadmin for user {$user->id} to ".($hasRole ? 'TRUE' : 'FALSE'));
            // Update column silently to avoid infinite loop (saved -> saved)
            $user->updateQuietly(['is_global_superadmin' => $hasRole]);
            // Re-fetch to ensure instances have correct value? No, instance is updated.
            $user->refresh();
        }

        if (! $hasRole) {
            return;
        }

        \Illuminate\Support\Facades\Log::info("SyncObserver: User {$user->id} IS superadmin. Syncing to all tenants.");

        // Sync to all tenants
        $allTenantIds = Tenant::pluck('id')->toArray();
        $changes = $user->tenants()->syncWithoutDetaching($allTenantIds);

        // Only touch if we actually attached new tenants
        if (! empty($changes['attached'])) {
            \Illuminate\Support\Facades\Log::info("SyncObserver: Attached tenants to user {$user->id}. Touching user to trigger ResourceSyncing.");
            // We don't need to manually setAttribute anymore because it's a real column and updated above.
            $user->touch();
        } else {
            \Illuminate\Support\Facades\Log::info("SyncObserver: No new tenants attached for user {$user->id}.");
        }
    }
}
