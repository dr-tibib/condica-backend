<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Products;
use App\Models\ProductSyncLog;
use Bunny\Storage\Client;
use Bunny\Storage\FileNotFoundException;
use Bunny\Storage\Region;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BunnyProductImageService
{
    private ?Client $client = null;

    public function syncAllProductsWithLog(?int $maxImages = null, bool $forceReupload = false, ?string $onlyStatus = null): ProductSyncLog
    {
        $log = ProductSyncLog::create([
            'source' => 'products:sync-images-to-bunny',
            'source_type' => 'bunny',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $stats = [
            'total_products' => 0,
            'products_processed' => 0,
            'products_updated' => 0,
            'images_uploaded' => 0,
            'images_skipped' => 0,
            'images_deleted' => 0,
            'images_failed' => 0,
            'failure_details' => [],
        ];

        try {
            $stats = $this->syncAllProducts($log, $maxImages, $forceReupload, $onlyStatus);

            $log->update([
                'status' => 'completed',
                'total_rows' => $stats['total_products'],
                'created_rows' => 0,
                'updated_rows' => $stats['products_updated'],
                'skipped_rows' => 0,
                'failed_rows' => $stats['images_failed'],
                'message' => 'Bunny image sync completed successfully.',
                'meta' => [
                    'total_products' => $stats['total_products'],
                    'products_processed' => $stats['products_processed'],
                    'products_updated' => $stats['products_updated'],
                    'images_attempted' => $stats['images_attempted'],
                    'images_uploaded' => $stats['images_uploaded'],
                    'images_skipped' => $stats['images_skipped'],
                    'images_deleted' => $stats['images_deleted'],
                    'images_failed' => $stats['images_failed'],
                    'max_images' => $maxImages,
                    'force_reupload' => $forceReupload,
                    'only_status' => $onlyStatus,
                    'limit_reached' => $stats['limit_reached'],
                ],
                'failure_details' => $stats['failure_details'],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'total_rows' => $stats['total_products'] ?? 0,
                'updated_rows' => $stats['products_updated'] ?? 0,
                'failed_rows' => $stats['images_failed'] ?? 0,
                'message' => $exception->getMessage(),
                'meta' => [
                    'total_products' => $stats['total_products'] ?? 0,
                    'products_processed' => $stats['products_processed'] ?? 0,
                    'products_updated' => $stats['products_updated'] ?? 0,
                    'images_attempted' => $stats['images_attempted'] ?? 0,
                    'images_uploaded' => $stats['images_uploaded'] ?? 0,
                    'images_skipped' => $stats['images_skipped'] ?? 0,
                    'images_deleted' => $stats['images_deleted'] ?? 0,
                    'images_failed' => $stats['images_failed'] ?? 0,
                    'max_images' => $maxImages,
                    'force_reupload' => $forceReupload,
                    'only_status' => $onlyStatus,
                    'limit_reached' => $stats['limit_reached'] ?? false,
                ],
                'failure_details' => [
                    [
                        'error' => $exception->getMessage(),
                    ],
                ],
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return $log->fresh();
    }

    /**
     * @return array{total_products: int, products_processed: int, products_updated: int, images_attempted: int, images_uploaded: int, images_skipped: int, images_deleted: int, images_failed: int, failure_details: array<int, array<string, mixed>>, limit_reached: bool}
     */
    public function syncAllProducts(?ProductSyncLog $log = null, ?int $maxImages = null, bool $forceReupload = false, ?string $onlyStatus = null): array
    {
        $query = Products::query();

        if ($onlyStatus !== null) {
            $query->where('status', $onlyStatus);
        }

        $stats = [
            'total_products' => $query->count(),
            'products_processed' => 0,
            'products_updated' => 0,
            'images_attempted' => 0,
            'images_uploaded' => 0,
            'images_skipped' => 0,
            'images_deleted' => 0,
            'images_failed' => 0,
            'failure_details' => [],
            'limit_reached' => false,
        ];

        if ($log) {
            $this->updateProgressLog($log, $stats, 'Starting Bunny image sync...');
        }

        $query->chunkById(100, function ($products) use (&$stats, $log, $maxImages, $forceReupload) {
            foreach ($products as $product) {
                if ($maxImages !== null && $stats['images_attempted'] >= $maxImages) {
                    $stats['limit_reached'] = true;

                    if ($log) {
                        $this->updateProgressLog(
                            $log,
                            $stats,
                            "Reached max attempted image limit of {$maxImages}. Stopping sync."
                        );
                    }

                    return false;
                }

                $currentProductNumber = $stats['products_processed'] + 1;

                if ($log) {
                    $this->updateProgressLog(
                        $log,
                        $stats,
                        "Processing product {$currentProductNumber} of {$stats['total_products']}: {$product->article_name}"
                    );
                }

                $stats['products_processed']++;

                $remainingImages = $maxImages !== null
                    ? max(0, $maxImages - $stats['images_attempted'])
                    : null;

                $result = $this->syncProductImages($product, $remainingImages, $forceReupload);

                $stats['products_updated'] += $result['product_updated'] ? 1 : 0;
                $stats['images_attempted'] += $result['images_attempted'];
                $stats['images_uploaded'] += $result['images_uploaded'];
                $stats['images_skipped'] += $result['images_skipped'];
                $stats['images_deleted'] += $result['images_deleted'];
                $stats['images_failed'] += $result['images_failed'];
                $stats['failure_details'] = array_merge($stats['failure_details'], $result['failure_details']);
                $stats['limit_reached'] = $stats['limit_reached'] || $result['limit_reached'];

                if ($log && ($stats['products_processed'] % 10 === 0 || $stats['products_processed'] === $stats['total_products'])) {
                    $this->updateProgressLog(
                        $log,
                        $stats,
                        "Processed {$stats['products_processed']} of {$stats['total_products']} products"
                    );
                }

                if ($stats['limit_reached']) {
                    if ($log) {
                        $this->updateProgressLog(
                            $log,
                            $stats,
                            "Reached max attempted image limit of {$maxImages}. Stopping sync."
                        );
                    }

                    return false;
                }
            }
        });

        return $stats;
    }

    /**
     * @return array{product_updated: bool, images_attempted: int, images_uploaded: int, images_skipped: int, images_deleted: int, images_failed: int, failure_details: array<int, array<string, mixed>>, limit_reached: bool}
     */
    public function syncProductImages(Products $product, ?int $maxImages = null, bool $forceReupload = false): array
    {
        $stats = [
            'product_updated' => false,
            'images_attempted' => 0,
            'images_uploaded' => 0,
            'images_skipped' => 0,
            'images_deleted' => 0,
            'images_failed' => 0,
            'failure_details' => [],
            'limit_reached' => false,
        ];

        // When forcing a reupload, clear Bunny mappings and collect original non-Bunny sources.
        $forceSources = null;

        if ($forceReupload) {
            // Delete all currently known Bunny images for this product.
            $this->deleteAllProductImagesForProduct($product);

            // Reset Bunny mappings.
            $product->bunny_image_mappings = null;

            // Prefer original sources from old_image_sources, falling back to current images if needed.
            $originalSources = $this->normalizeImagesJson($product->old_image_sources ?? []);
            if ($originalSources !== null) {
                $forceSources = array_values(array_filter(
                    $originalSources,
                    fn ($src) => is_string($src) && trim($src) !== '' && ! $this->isBunnyUrl($src)
                ));
                if ($forceSources === []) {
                    $forceSources = null;
                }
            }
        }

        $currentBunnyUrls = $this->collectBunnyUrlsFromProduct($product);
        $finalBunnyUrls = [];

        // For normal runs, walk individual image fields; for forced reupload based on sources,
        // we skip this and drive everything from the images list below.
        if (! $forceReupload || $forceSources === null) {
            foreach (Products::imageFields() as $field) {
                if ($maxImages !== null && $stats['images_attempted'] >= $maxImages) {
                    $stats['limit_reached'] = true;

                    break;
                }

                $source = $product->{$field};

                if (! is_string($source) || trim($source) === '') {
                    $stats['images_skipped']++;

                    continue;
                }

                if ($this->isBunnyUrl($source)) {
                    $finalBunnyUrls[] = $source;
                    $stats['images_skipped']++;

                    continue;
                }

                $existingBunnyUrl = $product->getBunnyUrlForSource($field, $source);
                if ($existingBunnyUrl !== null) {
                    $product->{$field} = $existingBunnyUrl;
                    $finalBunnyUrls[] = $existingBunnyUrl;
                    $stats['images_skipped']++;
                    $stats['product_updated'] = true;

                    continue;
                }

                $stats['images_attempted']++;

                try {
                    $product->rememberOldImageSource($field, $source);
                    $bunnyUrl = $this->uploadSource($source, $product, $field);
                    $product->recordBunnyMapping($field, $source, $bunnyUrl);
                    $product->{$field} = $bunnyUrl;
                    $finalBunnyUrls[] = $bunnyUrl;
                    $stats['images_uploaded']++;
                    $stats['product_updated'] = true;
                } catch (\Throwable $exception) {
                    $stats['images_failed']++;
                    $stats['failure_details'][] = $this->buildFailureDetail($product, $field, $source, $exception);
                }
            }
        }

        // For forced reupload, drive uploads from original non-Bunny sources; otherwise, use current images.
        $images = $forceSources !== null
            ? $forceSources
            : $this->normalizeImagesJson($product->images);
        if ($images !== null) {
            $updatedImages = [];
            $imagesChanged = false;

            foreach ($images as $index => $source) {
                if ($maxImages !== null && $stats['images_attempted'] >= $maxImages) {
                    $stats['limit_reached'] = true;

                    break;
                }

                if (! is_string($source) || trim($source) === '') {
                    $updatedImages[] = $source;
                    $stats['images_skipped']++;

                    continue;
                }

                if ($this->isBunnyUrl($source)) {
                    $updatedImages[] = $source;
                    $finalBunnyUrls[] = $source;
                    $stats['images_skipped']++;

                    continue;
                }

                $existingBunnyUrl = $product->getBunnyUrlForSource('images', $source);
                if ($existingBunnyUrl !== null) {
                    $updatedImages[] = $existingBunnyUrl;
                    $finalBunnyUrls[] = $existingBunnyUrl;
                    $stats['images_skipped']++;
                    $imagesChanged = true;

                    continue;
                }

                $stats['images_attempted']++;

                try {
                    $bunnyUrl = $this->uploadSource($source, $product, 'images', $index);
                    $product->recordBunnyMapping('images', $source, $bunnyUrl);
                    $updatedImages[] = $bunnyUrl;
                    $finalBunnyUrls[] = $bunnyUrl;
                    $stats['images_uploaded']++;
                    $imagesChanged = true;
                } catch (\Throwable $exception) {
                    $updatedImages[] = $source;
                    $stats['images_failed']++;
                    $stats['failure_details'][] = $this->buildFailureDetail($product, "images.{$index}", $source, $exception);
                }
            }

            if ($imagesChanged) {
                $product->rememberOldImageSource('images', $images);
                $product->images = json_encode($updatedImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $stats['product_updated'] = true;
            }

            // Also map images array into dedicated image fields:
            // image_link = first image, image_url_1 = second, image_url_2 = third, etc.
            $imagesForFields = $this->normalizeImagesJson($product->images);
            if ($imagesForFields !== null) {
                $fieldNames = Products::imageFields();

                foreach ($imagesForFields as $index => $url) {
                    if (! is_string($url) || trim($url) === '') {
                        continue;
                    }

                    if (! isset($fieldNames[$index])) {
                        continue;
                    }

                    $fieldName = $fieldNames[$index];
                    $url = trim($url);

                    if ($product->{$fieldName} !== $url) {
                        $product->rememberOldImageSource($fieldName, $product->{$fieldName});
                        $product->{$fieldName} = $url;
                        $stats['product_updated'] = true;
                    }
                }
            }
        }

        $toDelete = array_diff($currentBunnyUrls, array_unique($finalBunnyUrls));
        foreach ($toDelete as $url) {
            $path = $this->bunnyUrlToStoragePath($url);
            if ($path !== null) {
                try {
                    $this->client()->delete($path);
                    $stats['images_deleted']++;
                } catch (FileNotFoundException) {
                    // Already removed on Bunny
                }
            }
        }

        if ($stats['product_updated'] || $stats['images_deleted'] > 0) {
            $product->save();
        }

        return $stats;
    }

    /**
     * Delete all Bunny images currently associated with the given product.
     * Returns the number of successfully deleted images.
     */
    public function deleteAllProductImagesForProduct(Products $product): int
    {
        $urls = $this->collectBunnyUrlsFromProduct($product);
        $deleted = 0;

        foreach ($urls as $url) {
            $path = $this->bunnyUrlToStoragePath($url);

            if ($path === null) {
                continue;
            }

            try {
                $this->client()->delete($path);
                $deleted++;
            } catch (FileNotFoundException) {
                // Already removed on Bunny, ignore
            }
        }

        return $deleted;
    }

    /**
     * @return list<string>
     */
    private function collectBunnyUrlsFromProduct(Products $product): array
    {
        $urls = [];
        foreach (Products::imageFields() as $field) {
            $value = $product->{$field};
            if (is_string($value) && trim($value) !== '' && $this->isBunnyUrl($value)) {
                $urls[] = trim($value);
            }
        }
        $images = $this->normalizeImagesJson($product->images);
        if ($images !== null) {
            foreach ($images as $url) {
                if (is_string($url) && trim($url) !== '' && $this->isBunnyUrl($url)) {
                    $urls[] = trim($url);
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Extract storage path from a Bunny CDN URL so it can be passed to delete(). Returns null if not our CDN.
     */
    private function bunnyUrlToStoragePath(string $bunnyUrl): ?string
    {
        $baseUrl = (string) config('bunny.cdn_base_url');
        if ($baseUrl === '') {
            $zone = (string) config('bunny.storage_zone');
            $baseUrl = $zone !== '' ? 'https://'.$zone.'.b-cdn.net' : '';
        }
        $baseUrl = rtrim($baseUrl, '/');
        if ($baseUrl === '' || ! str_starts_with($bunnyUrl, $baseUrl.'/')) {
            if (! str_contains($bunnyUrl, '.b-cdn.net/')) {
                return null;
            }
        }
        $path = parse_url($bunnyUrl, PHP_URL_PATH);

        return $path !== null && $path !== '' ? ltrim($path, '/') : null;
    }

    public function uploadUploadedFile(UploadedFile $file, Products $product, string $field): string
    {
        $remotePath = $this->buildRemotePath($product, $field, $file->getClientOriginalName(), $file->extension());

        $this->client()->upload($file->getRealPath(), $remotePath);

        return $this->publicUrl($remotePath);
    }

    public function uploadSource(string $source, Products $product, string $field, ?int $index = null): string
    {
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            return $this->uploadRemoteSource($source, $product, $field, $index);
        }

        return $this->uploadLocalSource($source, $product, $field, $index);
    }

    private function uploadRemoteSource(string $source, Products $product, string $field, ?int $index = null): string
    {
        $response = Http::timeout(120)->get($source)->throw();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'bunny-product-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create temporary file for remote image.');
        }

        try {
            file_put_contents($temporaryPath, $response->body());

            $remotePath = $this->buildRemotePath(
                $product,
                $field,
                basename(parse_url($source, PHP_URL_PATH) ?: ($field.($index !== null ? '-'.$index : ''))),
                $this->guessExtensionFromSource($source)
            );

            $this->client()->upload($temporaryPath, $remotePath);

            return $this->publicUrl($remotePath);
        } finally {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function uploadLocalSource(string $source, Products $product, string $field, ?int $index = null): string
    {
        $localPath = $this->resolveLocalPath($source);

        if ($localPath === null) {
            throw new RuntimeException("Unable to resolve local image path [{$source}].");
        }

        $remotePath = $this->buildRemotePath(
            $product,
            $field,
            basename($localPath),
            pathinfo($localPath, PATHINFO_EXTENSION)
        );

        $this->client()->upload($localPath, $remotePath);

        return $this->publicUrl($remotePath);
    }

    private function resolveLocalPath(string $source): ?string
    {
        $trimmed = trim($source);

        $candidatePaths = [
            $trimmed,
            base_path($trimmed),
            public_path(ltrim($trimmed, '/')),
        ];

        if (str_starts_with($trimmed, '/storage/')) {
            $candidatePaths[] = storage_path('app/public/'.ltrim(Str::after($trimmed, '/storage/'), '/'));
        } else {
            $candidatePaths[] = storage_path('app/public/'.ltrim($trimmed, '/'));
        }

        foreach ($candidatePaths as $candidatePath) {
            if (is_string($candidatePath) && file_exists($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
    }

    /**
     * Normalize the images field into a flat list of individual URLs.
     *
     * Supports:
     * - JSON arrays of URLs
     * - Plain arrays
     * - Comma-separated strings of URLs
     * - Single URL strings
     *
     * @return list<string>|null
     */
    private function normalizeImagesJson(mixed $value): ?array
    {
        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = [$value];
            }
        } else {
            return null;
        }

        $urls = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $parts = preg_split('/\s*,\s*/', trim($item)) ?: [];
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $urls[] = $part;
                    }
                }
            } elseif (is_array($item)) {
                foreach ($this->normalizeImagesJson($item) ?? [] as $part) {
                    $urls[] = $part;
                }
            }
        }

        return $urls === [] ? null : array_values(array_unique($urls));
    }

    private function buildRemotePath(Products $product, string $field, string $originalName, ?string $extension = null): string
    {
        $productsPath = trim((string) config('bunny.products_path', 'products'), '/');
        $articleCode = $product->article_code;
        $identifier = (is_string($articleCode) && trim($articleCode) !== '')
            ? trim($articleCode)
            : 'Id'.($product->getKey() ?? (string) Str::uuid());

        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $filename = Str::slug($filename !== '' ? $filename : $field);
        $extension = $extension ?: pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? strtolower($extension) : 'jpg';

        return trim($productsPath.'/'.$identifier.'/'.$field.'-'.$filename.'-'.Str::random(8).'.'.$extension, '/');
    }

    private function publicUrl(string $remotePath): string
    {
        $baseUrl = (string) config('bunny.cdn_base_url');

        if ($baseUrl === '') {
            $zone = (string) config('bunny.storage_zone');

            if ($zone === '') {
                throw new RuntimeException('Bunny CDN base URL is not configured.');
            }

            $baseUrl = 'https://'.$zone.'.b-cdn.net';
        } else {
            $baseUrl = rtrim($baseUrl, '/');

            if (str_starts_with($baseUrl, 'http://')) {
                $baseUrl = 'https://'.substr($baseUrl, 7);
            } elseif (! str_starts_with($baseUrl, 'https://')) {
                $baseUrl = 'https://'.$baseUrl;
            }
        }

        return rtrim($baseUrl, '/').'/'.ltrim($remotePath, '/');
    }

    private function isBunnyUrl(string $source): bool
    {
        $baseUrl = (string) config('bunny.cdn_base_url');

        if ($baseUrl !== '' && str_starts_with($source, rtrim($baseUrl, '/').'/')) {
            return true;
        }

        if (str_contains($source, '.b-cdn.net/')) {
            return true;
        }

        // Treat legacy storage API URLs as Bunny-hosted too so we never re-upload them.
        if (str_contains($source, 'storage.bunnycdn.com/')) {
            return true;
        }

        return false;
    }

    private function guessExtensionFromSource(string $source): string
    {
        $extension = pathinfo(parse_url($source, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : 'jpg';
    }

    private function client(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $apiKey = (string) config('bunny.storage_api_key');
        $storageZone = (string) config('bunny.storage_zone');
        $storageRegion = (string) config('bunny.storage_region', Region::FALKENSTEIN);

        if ($apiKey === '' || $storageZone === '') {
            throw new RuntimeException('Bunny storage credentials are not configured.');
        }

        if (! array_key_exists($storageRegion, Region::LIST)) {
            throw new RuntimeException("Unsupported Bunny storage region [{$storageRegion}].");
        }

        $this->client = new Client($apiKey, $storageZone, $storageRegion);

        return $this->client;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFailureDetail(Products $product, string $field, string $source, \Throwable $exception): array
    {
        return [
            'product_id' => $product->getKey(),
            'article_name' => $product->article_name,
            'field' => $field,
            'source' => $source,
            'error' => $exception->getMessage(),
        ];
    }

    /**
     * @param  array{total_products:int,products_processed:int,products_updated:int,images_uploaded:int,images_skipped:int,images_failed:int,failure_details:array<int,array<string,mixed>>}  $stats
     * @param  array{total_products:int,products_processed:int,products_updated:int,images_attempted:int,images_uploaded:int,images_skipped:int,images_failed:int,failure_details:array<int,array<string,mixed>>,limit_reached:bool}  $stats
     */
    private function updateProgressLog(ProductSyncLog $log, array $stats, string $message): void
    {
        $log->forceFill([
            'status' => 'running',
            'total_rows' => $stats['total_products'],
            'updated_rows' => $stats['products_updated'],
            'failed_rows' => $stats['images_failed'],
            'message' => $message,
            'meta' => [
                'total_products' => $stats['total_products'],
                'products_processed' => $stats['products_processed'],
                'products_updated' => $stats['products_updated'],
                'images_attempted' => $stats['images_attempted'],
                'images_uploaded' => $stats['images_uploaded'],
                'images_skipped' => $stats['images_skipped'],
                'images_deleted' => $stats['images_deleted'],
                'images_failed' => $stats['images_failed'],
                'limit_reached' => $stats['limit_reached'],
            ],
        ])->save();
    }
}
