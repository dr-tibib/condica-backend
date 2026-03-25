<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Products;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class MerchantProProductFeedService
{
    private const int CHUNK_SIZE = 500;

    /**
     * @param  resource  $stream
     */
    public function streamProductsCsv($stream): void
    {
        $table = (new Products)->getTable();

        /** @var array<int, string> $columns */
        $columns = $this->resolveExportColumns($table);

        if ($columns === []) {
            return;
        }

        $this->writeCsvLine($stream, $columns);

        $this->eachFilteredProductChunk($table, function ($products) use ($stream, $columns): void {
            foreach ($products as $product) {
                $row = [];

                foreach ($columns as $column) {
                    $row[] = $this->formatPlainValue($product->getAttribute($column));
                }

                $this->writeCsvLine($stream, $row);
            }
        });
    }

    /**
     * @param  resource  $stream
     */
    public function streamProductsXml($stream): void
    {
        $table = (new Products)->getTable();

        /** @var array<int, string> $columns */
        $columns = $this->resolveExportColumns($table);

        if ($columns === []) {
            fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<products/>');

            return;
        }

        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        fwrite($stream, '<products>'."\n");

        $this->eachFilteredProductChunk($table, function ($products) use ($stream, $columns): void {
            foreach ($products as $product) {
                fwrite($stream, '  <product>'."\n");

                foreach ($columns as $column) {
                    $tag = $this->xmlElementName($column);
                    $text = $this->formatPlainValue($product->getAttribute($column));
                    fwrite(
                        $stream,
                        '    <'.$tag.'>'.$this->escapeXmlText($text).'</'.$tag.'>'."\n"
                    );
                }

                fwrite($stream, '  </product>'."\n");
            }
        });

        fwrite($stream, '</products>'."\n");
    }

    /**
     * @param  callable(\Illuminate\Support\Collection<int, Products>): void  $callback
     */
    private function eachFilteredProductChunk(string $table, callable $callback): void
    {
        $query = Products::query()->orderBy('id');
        $this->applyExportFilters($query, $table);
        $query->chunk(self::CHUNK_SIZE, $callback);
    }

    /**
     * @return array<int, string>
     */
    private function resolveExportColumns(string $table): array
    {
        /** @var array<int, string> $schemaColumns */
        $schemaColumns = Schema::getColumnListing($table);

        $configured = config('merchantpro_export.columns');

        if (! is_array($configured) || $configured === []) {
            return $schemaColumns;
        }

        $valid = array_flip($schemaColumns);
        $ordered = [];

        foreach ($configured as $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }
            if (isset($valid[$name])) {
                $ordered[] = $name;
            }
        }

        return $ordered;
    }

    private function applyExportFilters(Builder $query, string $table): void
    {
        $filters = config('merchantpro_export.filters');

        if (! is_array($filters) || $filters === []) {
            return;
        }

        foreach ($filters as $column => $condition) {
            if ($column === 'has_images') {
                if (! is_bool($condition)) {
                    continue;
                }
                $this->applyHasImagesFilter($query, $condition);

                continue;
            }

            if (! is_string($column) || $column === '' || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (is_array($condition)) {
                $query->whereIn($column, $condition);

                continue;
            }

            $query->where($column, $condition);
        }
    }

    private function applyHasImagesFilter(Builder $query, bool $mustHaveImages): void
    {
        $fields = Products::imageFields();

        if ($mustHaveImages) {
            $query->where(function (Builder $outer) use ($fields): void {
                foreach ($fields as $field) {
                    $outer->orWhere(function (Builder $inner) use ($field): void {
                        $inner->whereNotNull($field)->where($field, '!=', '');
                    });
                }
            });

            return;
        }

        foreach ($fields as $field) {
            $query->where(function (Builder $inner) use ($field): void {
                $inner->whereNull($field)->orWhere($field, '=', '');
            });
        }
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function writeCsvLine($stream, array $fields): void
    {
        $escaped = [];

        foreach ($fields as $field) {
            $escaped[] = '"'.str_replace('"', '""', $field).'"';
        }

        fwrite($stream, implode(';', $escaped)."\n");
    }

    private function formatPlainValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded === false ? '' : $encoded;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }

    private function xmlElementName(string $column): string
    {
        if ($column === '') {
            return 'field';
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/', $column) === 1) {
            return $column;
        }

        return 'c'.md5($column);
    }

    private function escapeXmlText(string $value): string
    {
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($cleaned, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
