<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return view('welcome', ['tenant' => tenant()]);
    });

    Route::get('/api/config', function () {
        return response()->json([
            'company_name' => tenant()->company_name,
            'logo_url' => tenant()->logo ? \Illuminate\Support\Facades\Storage::disk('public')->url(tenant()->logo) : null,
        ]);
    });
});
