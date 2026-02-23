<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tenant;
use App\Models\CentralUser;
use App\Models\Domain;
use Backpack\CRUD\app\Http\Controllers\AdminController;

class CentralDashboardController extends AdminController
{
    public function dashboard()
    {
        $this->data['title'] = trans('backpack::base.dashboard');
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin')     => backpack_url('dashboard'),
            trans('backpack::base.dashboard') => false,
        ];

        // Central Statistics
        $this->data['stats'] = [
            'tenants_count' => Tenant::count(),
            'domains_count' => Domain::count(),
            'central_users_count' => CentralUser::count(),
        ];

        $this->data['tenants'] = Tenant::with('domains')->latest()->take(5)->get();

        return view('admin.dashboard.central', $this->data);
    }
}
