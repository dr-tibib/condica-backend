<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPro;

use App\Http\Controllers\Controller;
use App\Services\MerchantProProductFeedService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductFeedExportController extends Controller
{
    public function csv(MerchantProProductFeedService $exportService): StreamedResponse
    {
        return response()->streamDownload(function () use ($exportService): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            try {
                $exportService->streamProductsCsv($out);
            } finally {
                fclose($out);
            }
        }, 'export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function xml(MerchantProProductFeedService $exportService): StreamedResponse
    {
        return response()->streamDownload(function () use ($exportService): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            try {
                $exportService->streamProductsXml($out);
            } finally {
                fclose($out);
            }
        }, 'export.xml', [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
