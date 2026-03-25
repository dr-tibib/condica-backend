<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant for central-domain access
    |--------------------------------------------------------------------------
    |
    | When the feed URL is opened on a central domain (see CENTRAL_DOMAINS), domain
    | tenancy does not run. Set MERCHANTPRO_FEED_TENANT_ID to a tenant UUID to export
    | that tenant's catalog. Tenant hostnames still use normal domain tenancy.
    |
    */

    'tenant_id' => env('MERCHANTPRO_FEED_TENANT_ID'),

    /*
    |--------------------------------------------------------------------------
    | MerchantPro product feed — row filters (AND)
    |--------------------------------------------------------------------------
    |
    | Only products matching every condition are exported. Supported keys:
    |
    | - has_images (bool): true = at least one of image_link / image_url_* is non-empty.
    | - Any real products table column: compared with where('=') to the given value.
    |   If the value is an array, whereIn() is used instead.
    | - Boolean columns (e.g. available_emag): use true / false.
    |
    | Use an empty array to include all products.
    |
    */

    'filters' => [
        // 'has_images' => true,
        // 'status' => 'active',
        'id' => '10959',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export columns (optional) — CSV and XML
    |--------------------------------------------------------------------------
    |
    | null — export every column from the products table (schema order).
    |
    | Or a list of column names in the desired order (unknown names are skipped).
    |
    */

    'columns' => null,
];
