<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CentralUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class SyncSuperAdminsToNewTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var TenantWithDatabase */
    protected $tenant;

    public function __construct(TenantWithDatabase $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Handle the event.
     */
    public function handle(): void
    {
        // Find all central users with the 'superadmin' role
        $superAdmins = CentralUser::role('superadmin')->get();

        foreach ($superAdmins as $superAdmin) {
            // Attach the new tenant to the superadmin
            // We use the tenant's ID as that's what attaches to the pivot
            $superAdmin->tenants()->syncWithoutDetaching([$this->tenant->getTenantKey()]);

            // To ensure ResourceSyncing is triggered immediately for this new connection,
            // we might need to 'touch' the user or manually trigger a sync.
            // However, typically just attaching is what establishes the link.
            // If ResourceSyncing relies on 'saved' event of the CentralUser to push to tenants,
            // then we should save the user to trigger that push to the new tenant.
            // But usually, attaching to the pivot is for 'access' rights.
            // The actual data sync (copying the user row to tenant DB) happens when CentralUser is saved.
            // Let's force a sync by touching the user timestamp, which triggers 'saved'.
            // Note: This might be expensive if there are many superadmins, but usually there are few.

            // Optimization: Only touch if we suspect it's needed. For now, let's trust the user's setup
            // that attaching is the primary goal, and if ResourceSyncing is correctly set up,
            // simply 'saving' the generic user updates naturally propagates.
            // BUT, for the user to *exist* in the tenant DB, they must be copied.
            // ResourceSyncing iterates over `tenants()` to copy data.
            // By attaching the tenant, we add it to `tenants()`.
            // Now we trigger the copy:
            $superAdmin->touch();
        }
    }
}
