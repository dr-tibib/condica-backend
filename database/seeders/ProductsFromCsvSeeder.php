<?php

namespace Database\Seeders;

use App\Models\Products;
use Illuminate\Database\Seeder;

class ProductsFromCsvSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data_export (2).csv');

        if (! file_exists($path)) {
            $this->command?->error("CSV file not found at {$path}");

            return;
        }

        if (! $handle = fopen($path, 'r')) {
            $this->command?->error('Unable to open products CSV file.');

            return;
        }

        Products::query()->delete();

        $header = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $id = $row[0] ?? null;
            if ($id === '' || $id === null) {
                continue;
            }

            $product = [
                'item_identifier' => (string) $id,
                'article_code' => $row[1] ?? null,
                'article_name' => $row[2] ?? '',
                'external_reference_id' => isset($row[3]) && $row[3] !== '' ? (string) $row[3] : null,
                'description' => $row[4] ?? null,
                'price' => isset($row[5]) && $row[5] !== '' ? (float) $row[5] : null,
                'product_price_net' => isset($row[6]) && $row[6] !== '' ? (float) $row[6] : null,
                'old_price' => isset($row[7]) && $row[7] !== '' ? (float) $row[7] : null,
                'old_price_net' => isset($row[8]) && $row[8] !== '' ? (float) $row[8] : null,
                'tax_value' => isset($row[9]) && $row[9] !== '' ? (float) $row[9] : null,
                'main_category' => $row[10] ?? null,
                'category' => isset($row[10]) && $row[10] !== ''
                    ? trim(explode('>', $row[10], 2)[0])
                    : null,
                'subcategory' => isset($row[10]) && str_contains($row[10], '>')
                    ? trim(explode('>', $row[10], 2)[1])
                    : null,
                'manufacturer' => $row[11] ?? null,
                'supplier' => $row[12] ?? null,
                'product_url' => $row[13] ?? null,
                'meta_title' => $row[14] ?? null,
                'meta_description' => $row[15] ?? null,
                'image_link' => $row[16] ?? null,
                'images' => isset($row[17]) && $row[17] !== '' ? json_encode([$row[17]]) : null,
                'quantity' => null,
                'stock' => isset($row[18]) && $row[18] !== '' ? (int) $row[18] : null,
                'availability' => $row[21] ?? null,
                'status' => $row[22] ?? null,
                'visibility' => $row[23] ?? null,
                'keywords' => $row[24] ?? null,
                'added_at' => isset($row[25]) && $row[25] !== '' ? $row[25] : null,
                'currency' => $row[26] ?? null,
                'image_url_1' => $row[27] ?? null,
                'image_url_2' => $row[28] ?? null,
                'image_url_3' => $row[29] ?? null,
                'image_url_4' => $row[30] ?? null,
                'image_url_5' => $row[31] ?? null,
                'image_url_6' => $row[32] ?? null,
                'image_url_8' => null,
                'image_url_9' => null,
                'image_url_10' => null,
            ];

            Products::create($product);
            $count++;
        }

        fclose($handle);

        $this->command?->info("Imported {$count} products from data_export (2).csv.");
    }
}
