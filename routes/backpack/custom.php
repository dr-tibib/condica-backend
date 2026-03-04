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
    'namespace' => 'App\\Http\\Controllers\\Admin',
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
    Route::get('dashboard', 'DashboardController@index')->name('backpack.dashboard');
    Route::get('dashboard/ai-insights', 'TenantAdminDashboardController@aiInsights')->name('backpack.dashboard.ai-insights');
    Route::crud('vehicle', 'VehicleCrudController');
    Route::crud('delegation-place', 'DelegationPlaceCrudController');
    Route::crud('delegation', 'DelegationCrudController');
    Route::crud('employee', 'EmployeeCrudController');
    Route::crud('domain', 'DomainCrudController');
    Route::get('employee-statistics/download-condica', 'EmployeeStatisticsController@downloadCondica');
    Route::crud('employee-statistics', 'EmployeeStatisticsController');
    Route::get('products', 'ProductsController')->name('backpack.products');
    Route::get('admin-center', 'AdminModuleController')->name('backpack.admin');
    Route::crud('products/products', 'ProductsCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
