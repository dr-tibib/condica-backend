<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\GoogleDriveImageSourceService;
use App\Services\ProductDriveImageImportService;
use Illuminate\Console\Command;

class ImportProductImagesFromGoogleDrive extends Command
{
    protected $signature = 'products:import-drive-images
        {tenant_id : Tenant ID to import images into}
        {folder_id? : Google Drive folder ID (optional if GOOGLE_DRIVE_FOLDER_ID is set)}
        {--access-token= : OAuth access token with Drive read access (or set GOOGLE_DRIVE_ACCESS_TOKEN)}
        {--sku-regex= : Regex with first capture group being SKU (default: /^([A-Za-z0-9_-]+)/)}
        {--limit= : Only process this many Drive files}
        {--show-imported : Print each imported image URL in terminal output}
        {--dry-run : Do not write to database}';

    protected $description = 'Fetch images from a Google Drive folder and attach them to products by SKU (as source URLs)';

    public function __construct(
        private readonly GoogleDriveImageSourceService $drive,
        private readonly ProductDriveImageImportService $importService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        @set_time_limit(240);
        @ini_set('max_execution_time', '240');

        $tenantId = (string) $this->argument('tenant_id');
        $folderId = (string) ($this->argument('folder_id') ?: env('GOOGLE_DRIVE_FOLDER_ID', ''));
        $accessToken = (string) ($this->option('access-token') ?: env('GOOGLE_DRIVE_ACCESS_TOKEN', ''));
        // Default SKU regex:
        // - 14424.jpg, 14424_1.jpg, 14424_2.heic => captures "14424"
        // - 20260309_14424.jpg, 20260309_14424_2.jpg => captures "14424"
        $skuRegex = (string) ($this->option('sku-regex') ?: '/^([A-Za-z0-9]+)(?:[_-]\d+)?/');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $showImported = (bool) $this->option('show-imported');
        $dryRun = (bool) $this->option('dry-run');

        if ($folderId === '') {
            $this->error('Missing folder ID. Provide <folder_id> or set GOOGLE_DRIVE_FOLDER_ID.');

            return self::FAILURE;
        }

        if ($accessToken === '') {
            $this->error('Missing access token. Provide --access-token=... or set GOOGLE_DRIVE_ACCESS_TOKEN.');

            return self::FAILURE;
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found.");

            return self::FAILURE;
        }

        tenancy()->initialize($tenant);

        try {
            $result = $this->importService->syncWithLog(
                folderId: $folderId,
                accessToken: $accessToken,
                limit: $limit,
                skuRegex: $skuRegex,
                dryRun: $dryRun
            );
            $log = $result['log'];
            $stats = $result['stats'];
            $importReport = $result['import_report'];

            $this->info('Google Drive image import completed.');
            $this->line("Log ID: {$log->id}");
            $this->line("Drive files processed: {$this->formatInt((int) $stats['drive_files_processed'])}");
            $this->line("SKUs found: {$this->formatInt((int) $stats['skus_found'])}");
            $this->line("Products matched: {$this->formatInt((int) $stats['products_matched'])}");
            $this->line("Products updated: {$this->formatInt((int) $stats['products_updated'])}".($dryRun ? ' (dry-run)' : ''));
            $this->line("Images imported: {$this->formatInt((int) $stats['images_imported'])}");
            $this->line("Products skipped (no match): {$this->formatInt((int) $stats['products_skipped'])}");

            if ($showImported && $importReport !== []) {
                $this->newLine();
                $this->info('Imported image details:');

                foreach ($importReport as $entry) {
                    $this->line("Product #{$entry['product_id']} (SKU: {$entry['sku']}):");

                    foreach ($entry['added'] as $url) {
                        $this->line("  - {$url}");
                    }
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            tenancy()->end();
        }
    }

    private function formatInt(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
