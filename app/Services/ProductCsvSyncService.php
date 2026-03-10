<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Products;
use App\Models\ProductSyncLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProductCsvSyncService
{
    public function sync(string $source): ProductSyncLog
    {
        $resolvedSource = $this->resolveSource($source);

        $log = ProductSyncLog::create([
            'source' => $source,
            'source_type' => $resolvedSource['type'],
            'status' => 'running',
            'started_at' => now(),
        ]);

        $stats = [
            'total_rows' => 0,
            'created_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'failure_details' => [],
            'match_breakdown' => [
                'article_code' => 0,
                'item_identifier' => 0,
                'external_reference_id' => 0,
                'created' => 0,
            ],
        ];

        try {
            $this->processFile($resolvedSource['path'], $stats);

            $log->update([
                'status' => 'completed',
                'total_rows' => $stats['total_rows'],
                'created_rows' => $stats['created_rows'],
                'updated_rows' => $stats['updated_rows'],
                'skipped_rows' => $stats['skipped_rows'],
                'failed_rows' => $stats['failed_rows'],
                'message' => 'Products CSV sync completed successfully.',
                'meta' => [
                    'resolved_path' => $resolvedSource['path'],
                    'match_breakdown' => $stats['match_breakdown'],
                ],
                'failure_details' => $stats['failure_details'],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'total_rows' => $stats['total_rows'],
                'created_rows' => $stats['created_rows'],
                'updated_rows' => $stats['updated_rows'],
                'skipped_rows' => $stats['skipped_rows'],
                'failed_rows' => $stats['failed_rows'] + 1,
                'message' => $exception->getMessage(),
                'meta' => [
                    'resolved_path' => $resolvedSource['path'],
                    'match_breakdown' => $stats['match_breakdown'],
                ],
                'failure_details' => $stats['failure_details'],
                'finished_at' => now(),
            ]);

            throw $exception;
        } finally {
            if ($resolvedSource['cleanup']) {
                ($resolvedSource['cleanup'])();
            }
        }

        return $log->fresh();
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function processFile(string $path, array &$stats): void
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new RuntimeException("Unable to open CSV file at {$path}.");
        }

        try {
            $header = fgetcsv($handle, 0, ';');

            if ($header === false) {
                throw new RuntimeException('CSV file is empty.');
            }

            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                $stats['total_rows']++;

                try {
                    $this->syncRow($header, $row, $stats);
                } catch (\Throwable $exception) {
                    $stats['failed_rows']++;
                    $stats['failure_details'][] = $this->buildFailureDetail($header, $row, $rowNumber, $exception);
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string|null>  $header
     * @param  array<int, string|null>  $row
     * @param  array<string, mixed>  $stats
     */
    private function syncRow(array $header, array $row, array &$stats): void
    {
        $data = array_combine($header, array_pad($row, count($header), null));

        if ($data === false) {
            $stats['skipped_rows']++;

            return;
        }

        $itemIdentifier = $this->nullableString($data['COD'] ?? null);
        $articleCode = $this->nullableString($data['CODINTERN'] ?? null);
        $externalReferenceId = $this->nullableString($data['CODEXTERN'] ?? null);

        if ($itemIdentifier === null && $articleCode === null && $externalReferenceId === null) {
            $stats['skipped_rows']++;

            return;
        }

        [$mainCategory, $category, $subcategory] = $this->parseClassPath(
            $this->nullableString($data['CLASA'] ?? null)
        );

        $productData = [
            'article_code' => $articleCode,
            'external_reference_id' => $externalReferenceId,
            'article_name' => $this->nullableString($data['DENUMIRE'] ?? null),
            'stock' => $this->nullableInt($data['STOC'] ?? null),
            'price' => $this->nullableDecimal($data['PRETSITE'] ?? null),
            'price_group1' => $this->nullableDecimal($data['PRETD1'] ?? null),
            'price_group2' => $this->nullableDecimal($data['PRETD2'] ?? null),
            'price_group3' => $this->nullableDecimal($data['PRETD3'] ?? null),
            'price_group4' => $this->nullableDecimal($data['PRETA'] ?? null),
            'main_category' => $mainCategory,
            'category' => $category,
            'subcategory' => $subcategory,
            'availability' => $this->nullableInt($data['STOC'] ?? null) > 0
                ? 'Disponibil in stoc'
                : 'Acest produs nu este disponibil in stoc',
        ];

        [$product, $matchType] = $this->findExistingProduct(
            $articleCode,
            $itemIdentifier,
            $externalReferenceId
        );

        if (! $product && ($productData['article_name'] ?? null) === null) {
            throw new RuntimeException('Cannot create a new product without DENUMIRE / article_name.');
        }

        if (! $product) {
            $product = new Products;
            $stats['created_rows']++;
            $stats['match_breakdown']['created']++;

            if ($itemIdentifier !== null) {
                $product->item_identifier = $itemIdentifier;
            }
        } else {
            $stats['updated_rows']++;
            $stats['match_breakdown'][$matchType]++;
        }

        $product->fill(array_filter(
            $productData,
            static fn (mixed $value): bool => $value !== null
        ));

        if ($product->exists && $articleCode !== null) {
            $product->article_code = $articleCode;
        }

        if ($product->exists && $externalReferenceId !== null) {
            $product->external_reference_id = $externalReferenceId;
        }

        $product->save();
    }

    /**
     * @return array{0: Products|null, 1: string}
     */
    private function findExistingProduct(
        ?string $articleCode,
        ?string $itemIdentifier,
        ?string $externalReferenceId
    ): array {
        if ($articleCode !== null) {
            $product = Products::query()->where('article_code', $articleCode)->first();

            if ($product) {
                return [$product, 'article_code'];
            }
        }

        if ($itemIdentifier !== null) {
            $product = Products::query()->where('item_identifier', $itemIdentifier)->first();

            if ($product) {
                return [$product, 'item_identifier'];
            }
        }

        if ($externalReferenceId !== null) {
            $product = Products::query()->where('external_reference_id', $externalReferenceId)->first();

            if ($product) {
                return [$product, 'external_reference_id'];
            }
        }

        return [null, 'created'];
    }

    /**
     * @return array{path: string, type: string, cleanup: null|\Closure}
     */
    private function resolveSource(string $source): array
    {
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $response = Http::timeout(120)->get($source)->throw();
            $temporaryPath = tempnam(sys_get_temp_dir(), 'products-sync-');

            if ($temporaryPath === false) {
                throw new RuntimeException('Unable to create temporary file for remote CSV.');
            }

            file_put_contents($temporaryPath, $response->body());

            return [
                'path' => $temporaryPath,
                'type' => 'remote',
                'cleanup' => static function () use ($temporaryPath): void {
                    if (file_exists($temporaryPath)) {
                        unlink($temporaryPath);
                    }
                },
            ];
        }

        $candidatePaths = [$source];

        if (! str_starts_with($source, '/')) {
            $candidatePaths[] = base_path($source);
        }

        foreach ($candidatePaths as $candidatePath) {
            if (file_exists($candidatePath)) {
                return [
                    'path' => $candidatePath,
                    'type' => 'local',
                    'cleanup' => null,
                ];
            }
        }

        throw new RuntimeException("CSV source not found: {$source}");
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function parseClassPath(?string $classPath): array
    {
        if ($classPath === null) {
            return [null, null, null];
        }

        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode('.', $classPath)
        )));

        if ($parts === []) {
            return [null, null, null];
        }

        $mainCategory = implode(' > ', $parts);
        $category = $parts[0] ?? null;
        $subcategory = count($parts) > 1 ? implode(' > ', array_slice($parts, 1)) : null;

        return [$mainCategory, $category, $subcategory];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value, " \t\n\r\0\x0B\"") : null;
        $value = $value !== null ? $this->normalizeEncoding($value) : null;

        return $value === '' || $value === '...' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        $normalized = $this->nullableString($value);

        return $normalized === null ? null : (int) $normalized;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        $normalized = $this->nullableString($value);

        return $normalized === null ? null : (float) str_replace(',', '.', $normalized);
    }

    /**
     * @param  array<int, string|null>  $header
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>
     */
    private function buildFailureDetail(array $header, array $row, int $rowNumber, \Throwable $exception): array
    {
        $data = array_combine($header, array_pad($row, count($header), null)) ?: [];

        return [
            'row_number' => $rowNumber,
            'error' => $this->normalizeEncoding($exception->getMessage()),
            'identifiers' => [
                'COD' => $this->nullableString($data['COD'] ?? null),
                'CODINTERN' => $this->nullableString($data['CODINTERN'] ?? null),
                'CODEXTERN' => $this->nullableString($data['CODEXTERN'] ?? null),
            ],
            'row' => [
                'DENUMIRE' => $this->nullableString($data['DENUMIRE'] ?? null),
                'STOC' => $this->nullableString($data['STOC'] ?? null),
                'PRETSITE' => $this->nullableString($data['PRETSITE'] ?? null),
                'PRETD1' => $this->nullableString($data['PRETD1'] ?? null),
                'PRETD2' => $this->nullableString($data['PRETD2'] ?? null),
                'PRETD3' => $this->nullableString($data['PRETD3'] ?? null),
                'PRETA' => $this->nullableString($data['PRETA'] ?? null),
                'CLASA' => $this->nullableString($data['CLASA'] ?? null),
            ],
        ];
    }

    private function normalizeEncoding(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $normalized = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        return mb_check_encoding($normalized, 'UTF-8')
            ? $normalized
            : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }
}
