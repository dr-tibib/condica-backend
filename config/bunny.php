<?php

return [
    'storage_api_key' => env('BUNNY_STORAGE_API_KEY'),
    'storage_zone' => env('BUNNY_STORAGE_ZONE'),
    'storage_region' => env('BUNNY_STORAGE_REGION', 'de'),
    'cdn_base_url' => rtrim((string) env('BUNNY_CDN_BASE_URL', ''), '/'),
    'products_path' => trim((string) env('BUNNY_PRODUCTS_PATH', 'products'), '/'),
];
