<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class LeaveRequestCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\LeaveRequest::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/leave-request');
        CRUD::setEntityNameStrings('leave request', 'leave requests');
    }

    protected function setupListOperation()
    {
        CRUD::column('user')->type('relationship')->attribute('name')->label('Employee');
        CRUD::column('leaveType')->type('relationship')->attribute('name')->label('Type');
        CRUD::column('start_date')->type('date');
        CRUD::column('end_date')->type('date');
        CRUD::column('total_days')->type('number')->decimals(1);
        CRUD::column('status');
    }

    protected function setupShowOperation()
    {
        CRUD::column('user')->type('relationship')->attribute('name')->label('Employee');
        CRUD::column('leaveType')->type('relationship')->attribute('name')->label('Type');
        CRUD::column('start_date')->type('date');
        CRUD::column('end_date')->type('date');
        CRUD::column('total_days');
        CRUD::column('status');
        CRUD::column('medical_code');
        CRUD::column('medical_certificate_series');
        CRUD::column('medical_certificate_number');
        CRUD::column('attachment_path')->type('upload')->label('Attachment');
        CRUD::column('rejection_reason');
        CRUD::column('approver')->type('relationship')->attribute('name')->label('Approved By');
    }

    protected function setupUpdateOperation()
    {
        CRUD::field('status')->type('enum')->options([
            'PENDING' => 'PENDING',
            'APPROVED' => 'APPROVED',
            'REJECTED' => 'REJECTED',
            'CANCELLED' => 'CANCELLED',
        ]);
        CRUD::field('rejection_reason');
    }
}
