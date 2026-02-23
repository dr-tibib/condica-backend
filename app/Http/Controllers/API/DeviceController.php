<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Register or update a device for push notifications.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', 'in:ios,android'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:50'],
        ]);

        $device = Device::updateOrCreate(
            [
                'employee_id' => $request->user()->employee->id,
                'device_token' => $validated['device_token'],
            ],
            [
                'device_name' => $validated['device_name'] ?? null,
                'platform' => $validated['platform'],
                'app_version' => $validated['app_version'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'last_active_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered successfully.',
            'device' => [
                'id' => $device->id,
                'device_token' => $device->device_token,
                'device_name' => $device->device_name,
                'platform' => $device->platform,
            ],
        ], 201);
    }
}
