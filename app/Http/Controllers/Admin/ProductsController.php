<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductImagesFromGoogleDriveJob;
use App\Jobs\SyncProductImagesToBunnyJob;
use App\Jobs\SyncProductsFromCsvJob;
use App\Models\ProductSyncLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.products.index', [
            'lastSiteSyncLog' => ProductSyncLog::query()
                ->where('source', 'site.csv')
                ->latest('id')
                ->first(),
            'lastBunnySyncLog' => ProductSyncLog::query()
                ->where('source_type', 'bunny')
                ->latest('id')
                ->first(),
            'lastGoogleDriveSyncLog' => ProductSyncLog::query()
                ->where('source_type', 'google_drive')
                ->latest('id')
                ->first(),
        ]);
    }

    public function syncSiteCsv(): RedirectResponse
    {
        $tenantId = (string) tenant('id');
        SyncProductsFromCsvJob::dispatch($tenantId, 'site.csv')->onConnection('database');

        return redirect()
            ->back()
            ->with('success', 'Products sync for site.csv has been queued.');
    }

    public function syncImagesToBunny(): RedirectResponse
    {
        $tenantId = (string) tenant('id');
        SyncProductImagesToBunnyJob::dispatch($tenantId)->onConnection('database');

        return redirect()
            ->back()
            ->with('success', 'Product image sync to Bunny CDN has been queued.');
    }

    public function syncImagesFromGoogleDrive(): RedirectResponse
    {
        $tenantId = (string) tenant('id');
        SyncProductImagesFromGoogleDriveJob::dispatch($tenantId)->onConnection('database');

        return redirect()
            ->back()
            ->with('success', 'Google Drive image import has been queued.');
    }
}
