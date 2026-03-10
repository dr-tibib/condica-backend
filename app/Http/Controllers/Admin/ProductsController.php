<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductsFromCsvJob;
use App\Models\ProductSyncLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.products.index', [
            'lastSyncLog' => ProductSyncLog::query()->latest('id')->first(),
        ]);
    }

    public function syncSiteCsv(): RedirectResponse
    {
        $tenantId = (string) tenant('id');

        SyncProductsFromCsvJob::dispatch($tenantId, 'site.csv');

        return redirect()
            ->back()
            ->with('success', 'Products sync for site.csv has been triggered.');
    }
}
