<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ProductCsvSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncProductsFromCsvJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $source
    ) {}

    public function handle(ProductCsvSyncService $syncService): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            throw new RuntimeException("Tenant {$this->tenantId} not found.");
        }

        tenancy()->initialize($tenant);

        try {
            $syncService->sync($this->source);
        } finally {
            tenancy()->end();
        }
    }
}
