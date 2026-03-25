<?php

use App\Http\Controllers\MerchantPro\ProductFeedExportController;
use App\Http\Middleware\InitializeMerchantProFeedTenancy;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ro', 'hu', 'de'])) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('lang.switch');

Route::middleware([InitializeMerchantProFeedTenancy::class])->group(function (): void {
    Route::get('merchantpro/products/feed/export.csv', [ProductFeedExportController::class, 'csv'])
        ->name('merchantpro.products.feed.export.csv');
    Route::get('merchantpro/products/feed/export.xml', [ProductFeedExportController::class, 'xml'])
        ->name('merchantpro.products.feed.export.xml');
});
