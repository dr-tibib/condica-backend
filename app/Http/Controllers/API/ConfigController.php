<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Get tenant configuration for the frontend.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'company_name' => tenant('company_name'),
            'logo_url' => tenant('logo') ? asset('storage/' . tenant('logo')) : null,
            'code_length' => (int) (tenant('code_length') ?? 3),
        ]);
    }
}
