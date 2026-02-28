<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        if (function_exists('tenant') && tenant()) {
            return app(TenantAdminDashboardController::class)->dashboard();
        }

        return app(CentralDashboardController::class)->dashboard();
    }
}
