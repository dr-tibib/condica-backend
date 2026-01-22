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
        $data = tenant()->data ?? [];

        return response()->json([
            'company_name' => $data['company_name'] ?? null,
            'logo_url' => isset($data['logo']) ? asset('storage/' . $data['logo']) : null,
            'code_length' => (int) ($data['code_length'] ?? 3),
        ]);
    }
}
