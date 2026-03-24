<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncProductsFromCsvJob;
use App\Models\Tenant;
use App\Services\ProductCsvSyncService;
use Illuminate\Console\Command;

class SyncProductsFromCsvCommand extends Command
{
    protected $signature = 'products:sync-csv
        {tenant_id : Tenant ID to sync}
        {source : Local file path or remote URL to the CSV file}
        {--now : Run immediately instead of queueing the job}';

    protected $description = 'Sync products from a local or remote site CSV file';

    public function __construct(
        private readonly ProductCsvSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = (string) $this->argument('tenant_id');
        $source = (string) $this->argument('source');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found.");

            return self::FAILURE;
        }

        if ($this->option('now')) {
            tenancy()->initialize($tenant);

            try {
                $log = $this->syncService->sync($source);
            } finally {
                tenancy()->end();
            }

            $this->info("Products sync completed. Log #{$log->id}");
            $this->line("Created: {$log->created_rows}");
            $this->line("Updated: {$log->updated_rows}");
            $this->line("Skipped: {$log->skipped_rows}");
            $this->line("Failed: {$log->failed_rows}");

            return self::SUCCESS;
        }

        SyncProductsFromCsvJob::dispatch($tenantId, $source)->onConnection('database');

        $this->info("Products sync job dispatched for tenant {$tenantId}.");

        return self::SUCCESS;
    }
}
