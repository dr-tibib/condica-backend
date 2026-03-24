<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\BunnyProductImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncProductImagesToBunnyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tenantId,
        public ?int $maxImages = null,
        public bool $forceReupload = false,
        public ?string $onlyStatus = null
    ) {}

    public function handle(BunnyProductImageService $bunnyProductImageService): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            throw new RuntimeException("Tenant {$this->tenantId} not found.");
        }

        tenancy()->initialize($tenant);

        try {
            $bunnyProductImageService->syncAllProductsWithLog($this->maxImages, $this->forceReupload, $this->onlyStatus);
        } finally {
            tenancy()->end();
        }
    }
}
