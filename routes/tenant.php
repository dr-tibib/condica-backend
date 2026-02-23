<?php

declare(strict_types=1);

use App\Http\Controllers\API\ConfigController;
use App\Http\Controllers\API\DelegationController;
use App\Http\Controllers\API\KioskController;
use App\Http\Controllers\API\LeaveController;
use App\Http\Controllers\API\WorkplaceController;
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
    Route::get('/delegation-test', function () {
        return view('welcome', ['tenant' => tenant()]);
    });
});

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api')->group(function () {
    Route::get('/config', [ConfigController::class, 'index']);

    Route::prefix('kiosk')->group(function () {
        Route::get('/dashboard', [KioskController::class, 'getDashboardData']);
        Route::get('/employees/status', [KioskController::class, 'getAllEmployeesStatus']);
        Route::post('/submit-code', [KioskController::class, 'submitCode']);
        Route::post('/end-delegation-schedule', [KioskController::class, 'endDelegationWithSchedule']);
        
        // New State Machine Endpoints
        Route::post('/handle-late-start', [KioskController::class, 'handleLateStart']);
        Route::post('/handle-shift-correction', [KioskController::class, 'handleShiftCorrection']);
        Route::post('/cancel-delegation', [KioskController::class, 'cancelDelegation']);
        Route::post('/handle-leave-submission', [KioskController::class, 'submitLeave']);

        Route::get('/vehicles', [KioskController::class, 'getVehicles']);
        Route::get('/saved-places', [KioskController::class, 'getSavedPlaces']);
        Route::get('/places/search', [KioskController::class, 'searchPlaces']);
    });

    Route::prefix('delegations')->group(function () {
        Route::get('/', [DelegationController::class, 'index']);
        Route::post('/', [DelegationController::class, 'store']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/workplaces', [WorkplaceController::class, 'index']);
    });

    Route::prefix('v1/leave')->group(function () {
        Route::get('balance', [LeaveController::class, 'balance']);
        Route::post('request', [LeaveController::class, 'request']);
        Route::post('approve', [LeaveController::class, 'approve']);
        Route::get('team-calendar', [LeaveController::class, 'teamCalendar']);
    });

    Route::get('v1/admin/export/payroll', [LeaveController::class, 'exportPayroll']);
});
