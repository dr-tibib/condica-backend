<?php

namespace App\Console\Commands;

use App\Jobs\SyncOldCondicaJob;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SyncOldCondicaSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'condica:sync-old {tenant_id?} {--start-date=2000-01-01}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize data from the old Condica system to the new one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $startDate = $this->option('start-date');

        if ($tenantId) {
            $this->syncForTenant($tenantId, $startDate);
        } else {
            $tenants = Tenant::all();
            if ($tenants->isEmpty()) {
                $this->error('No tenants found.');
                return 1;
            }

            foreach ($tenants as $tenant) {
                $this->syncForTenant($tenant->id, $startDate);
            }
        }

        $this->info('Sync jobs dispatched successfully.');
        return 0;
    }

    private function syncForTenant(string $tenantId, string $startDate): void
    {
        $this->info("Dispatching sync job for tenant: {$tenantId} starting from {$startDate}");
        SyncOldCondicaJob::dispatch($tenantId, $startDate);
    }
}
