<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('tenant', 'TenantCrudController');
    Route::crud('workplace', 'WorkplaceCrudController');
    Route::crud('department', 'DepartmentCrudController');
    Route::crud('presence-event', 'PresenceEventCrudController');
    Route::get('presence-event/export', 'PresenceEventCrudController@export');
    Route::crud('workplace-presence', 'WorkplacePresenceDashboardController');
    Route::crud('leave-type', 'LeaveTypeCrudController');
    Route::crud('public-holiday', 'PublicHolidayCrudController');
    Route::crud('leave-request', 'LeaveRequestCrudController');
    Route::get('team-command-center/export', 'TeamCommandCenterController@generateAttendanceSheet');
    Route::crud('team-command-center', 'TeamCommandCenterController');
    Route::get('dashboard', 'EmployeeDashboardController@dashboard')->name('backpack.dashboard');
    Route::crud('vehicle', 'VehicleCrudController');
    Route::crud('delegation-place', 'DelegationPlaceCrudController');
    Route::crud('delegation', 'DelegationCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
