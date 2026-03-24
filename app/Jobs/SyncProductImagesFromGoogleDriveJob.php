<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ProductDriveImageImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncProductImagesFromGoogleDriveJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tenantId,
        public ?int $limit = null,
        public ?string $skuRegex = null,
        public bool $dryRun = false
    ) {}

    public function handle(ProductDriveImageImportService $importService): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            throw new RuntimeException("Tenant {$this->tenantId} not found.");
        }

        $folderId = (string) env('GOOGLE_DRIVE_FOLDER_ID', '');
        $accessToken = (string) env('GOOGLE_DRIVE_ACCESS_TOKEN', '');

        if ($folderId === '' || $accessToken === '') {
            throw new RuntimeException('GOOGLE_DRIVE_FOLDER_ID and GOOGLE_DRIVE_ACCESS_TOKEN must be configured.');
        }

        tenancy()->initialize($tenant);

        try {
            $importService->syncWithLog(
                folderId: $folderId,
                accessToken: $accessToken,
                limit: $this->limit,
                skuRegex: $this->skuRegex ?: '/^([A-Za-z0-9_-]+)/',
                dryRun: $this->dryRun
            );
        } finally {
            tenancy()->end();
        }
    }
}
