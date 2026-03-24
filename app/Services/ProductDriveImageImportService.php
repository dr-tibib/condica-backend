<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Products;
use App\Models\ProductSyncLog;
use RuntimeException;

class ProductDriveImageImportService
{
    public function __construct(
        private readonly GoogleDriveImageSourceService $drive
    ) {}

    /**
     * @return array{log: ProductSyncLog, imported_image_count: int, import_report: array<int, array{product_id:int, sku:string, added:list<string>}>, stats: array<string,int>}
     */
    public function syncWithLog(
        string $folderId,
        string $accessToken,
        ?int $limit = null,
        string $skuRegex = '/^([A-Za-z0-9_-]+)/',
        bool $dryRun = false
    ): array {
        if ($folderId === '') {
            throw new RuntimeException('Missing Google Drive folder ID.');
        }

        if ($accessToken === '') {
            throw new RuntimeException('Missing Google Drive access token.');
        }

        $log = ProductSyncLog::create([
            'source' => 'google_drive:'.$folderId,
            'source_type' => 'google_drive',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $stats = [
            'drive_files_processed' => 0,
            'skus_found' => 0,
            'products_matched' => 0,
            'products_updated' => 0,
            'products_skipped' => 0,
            'images_imported' => 0,
            'failed_rows' => 0,
        ];
        $importReport = [];
        $failureDetails = [];

        try {
            $files = $this->drive->listImagesInFolder($folderId, $accessToken, $limit);
            $stats['drive_files_processed'] = count($files);

            $bySku = [];
            foreach ($files as $file) {
                $sku = $this->extractSkuFromFilename($file['name'], $skuRegex);

                if ($sku === null) {
                    continue;
                }

                $bySku[$sku][] = $this->drive->buildPublicDownloadUrl($file['id']);
            }

            $stats['skus_found'] = count($bySku);

            foreach ($bySku as $sku => $urls) {
                try {
                    $urls = array_values(array_unique(array_map('trim', $urls)));
                    $product = Products::query()->where('article_code', $sku)->first()
                        ?? Products::query()->where('item_identifier', $sku)->first();

                    if (! $product) {
                        $stats['products_skipped']++;

                        continue;
                    }

                    $stats['products_matched']++;
                    $existingImages = $this->normalizeImagesField($product->images);
                    $merged = array_values(array_unique(array_merge($existingImages, $urls)));
                    $newlyAdded = array_values(array_diff($merged, $existingImages));

                    if ($merged === $existingImages) {
                        continue;
                    }

                    if (! $dryRun) {
                        Products::withoutEvents(function () use ($product, $merged): void {
                            $product->rememberOldImageSource('images', $merged);
                            $product->images = json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            $product->save();
                        });
                    }

                    $stats['products_updated']++;
                    $stats['images_imported'] += count($newlyAdded);
                    $importReport[] = [
                        'product_id' => (int) $product->id,
                        'sku' => $sku,
                        'added' => $newlyAdded,
                    ];
                } catch (\Throwable $exception) {
                    $stats['failed_rows']++;
                    $failureDetails[] = [
                        'sku' => $sku,
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            $log->update([
                'status' => 'completed',
                'total_rows' => $stats['drive_files_processed'],
                'created_rows' => 0,
                'updated_rows' => $stats['products_updated'],
                'skipped_rows' => $stats['products_skipped'],
                'failed_rows' => $stats['failed_rows'],
                'message' => 'Google Drive image import completed successfully.',
                'meta' => [
                    'folder_id' => $folderId,
                    'dry_run' => $dryRun,
                    ...$stats,
                ],
                'failure_details' => $failureDetails,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'total_rows' => $stats['drive_files_processed'],
                'created_rows' => 0,
                'updated_rows' => $stats['products_updated'],
                'skipped_rows' => $stats['products_skipped'],
                'failed_rows' => max(1, $stats['failed_rows']),
                'message' => $exception->getMessage(),
                'meta' => [
                    'folder_id' => $folderId,
                    'dry_run' => $dryRun,
                    ...$stats,
                ],
                'failure_details' => $failureDetails,
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return [
            'log' => $log->fresh(),
            'imported_image_count' => $stats['images_imported'],
            'import_report' => $importReport,
            'stats' => $stats,
        ];
    }

    private function extractSkuFromFilename(string $filename, string $pattern): ?string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/^\d{8}[_-]([A-Za-z0-9_-]+)/', $basename, $datePrefixed)) {
            $sku = trim((string) $datePrefixed[1]);

            return $sku !== '' ? $sku : null;
        }

        $ok = @preg_match($pattern, $filename, $matches);

        if ($ok !== 1 || ! isset($matches[1])) {
            return null;
        }

        $sku = trim((string) $matches[1]);

        return $sku !== '' ? $sku : null;
    }

    /**
     * @return list<string>
     */
    private function normalizeImagesField(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $value))));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $decoded))));
        }

        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }
}
