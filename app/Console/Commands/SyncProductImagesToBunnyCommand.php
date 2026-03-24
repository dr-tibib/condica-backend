<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncProductImagesToBunnyJob;
use App\Models\Tenant;
use App\Services\BunnyProductImageService;
use Illuminate\Console\Command;

class SyncProductImagesToBunnyCommand extends Command
{
    protected $signature = 'products:sync-images-to-bunny
        {tenant_id : Tenant ID to sync}
        {--max-images= : Stop after uploading this many images}
        {--force-reupload : Reupload all images from non-Bunny sources and reset Bunny mappings}
        {--only-status= : Only sync products with this status (e.g. active)}
        {--now : Run immediately instead of queueing the job}';

    protected $description = 'Upload existing product images to Bunny CDN';

    public function __construct(
        private readonly BunnyProductImageService $bunnyProductImageService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        @set_time_limit(240);
        @ini_set('max_execution_time', '240');

        $tenantId = (string) $this->argument('tenant_id');
        $maxImagesOption = $this->option('max-images');
        $maxImages = $maxImagesOption !== null ? (int) $maxImagesOption : null;
        $forceReupload = (bool) $this->option('force-reupload');
        $onlyStatus = $this->option('only-status') ?: null;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found.");

            return self::FAILURE;
        }

        if (! $this->option('now')) {
            SyncProductImagesToBunnyJob::dispatch($tenantId, $maxImages, $forceReupload, $onlyStatus)->onConnection('database');

            $this->info("Bunny image sync job dispatched for tenant {$tenantId}.");

            return self::SUCCESS;
        }

        tenancy()->initialize($tenant);

        try {
            $log = $this->bunnyProductImageService->syncAllProductsWithLog($maxImages, $forceReupload, $onlyStatus);
            $stats = $log->meta ?? [];
        } finally {
            tenancy()->end();
        }

        $this->info("Bunny image sync completed. Log #{$log->id}");
        $this->line('Products processed: '.($stats['products_processed'] ?? 0));
        $this->line('Products updated: '.($stats['products_updated'] ?? 0));
        $this->line('Images uploaded: '.($stats['images_uploaded'] ?? 0));
        $this->line('Images skipped: '.($stats['images_skipped'] ?? 0));
        $this->line('Images deleted: '.($stats['images_deleted'] ?? 0));
        $this->line('Images failed: '.($stats['images_failed'] ?? 0));

        return self::SUCCESS;
    }
}
