<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class LeaveTypeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\LeaveType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/leave-type');
        CRUD::setEntityNameStrings('leave type', 'leave types');
    }

    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('is_paid')->type('boolean');
        CRUD::column('requires_document')->type('boolean');
        CRUD::column('affects_annual_quota')->type('boolean');
        CRUD::column('medical_code_required')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::field('name');
        CRUD::field('is_paid')->type('checkbox');
        CRUD::field('requires_document')->type('checkbox');
        CRUD::field('affects_annual_quota')->type('checkbox');
        CRUD::field('medical_code_required')->type('checkbox');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
