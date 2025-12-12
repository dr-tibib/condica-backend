<?php

declare(strict_types=1);

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\PresenceController;
use App\Http\Controllers\API\WorkplaceController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [LoginController::class, 'login']);

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/user', function () {
        return response()->json(request()->user());
    });

    // Presence tracking
    Route::prefix('presence')->group(function () {
        Route::post('/check-in', [PresenceController::class, 'checkIn']);
        Route::post('/check-out', [PresenceController::class, 'checkOut']);
        Route::get('/current', [PresenceController::class, 'current']);
        Route::get('/history', [PresenceController::class, 'history']);
        Route::get('/today', [PresenceController::class, 'today']);
    });

    // Workplaces
    Route::get('/workplaces', [WorkplaceController::class, 'index']);

    // Devices
    Route::post('/devices/register', [DeviceController::class, 'register']);
});
