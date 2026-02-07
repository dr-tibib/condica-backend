<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Employee;

/**
 * Class EmployeeCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EmployeeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\FetchOperation;

    public function setup()
    {
        CRUD::setModel(Employee::class);
        CRUD::setRoute(backpack_url('employee'));
        CRUD::setEntityNameStrings('employee', 'employees');
    }

    protected function setupListOperation()
    {
        CRUD::column('first_name')->label('First Name');
        CRUD::column('last_name')->label('Last Name');

        CRUD::column('email')->type('email');
        CRUD::column('phone');

        CRUD::column('department')->type('relationship');
        CRUD::column('workplace')->type('relationship');

        CRUD::column('status')
            ->label('Status')
            ->type('closure')
            ->function(function($entry) {
                if (method_exists($entry, 'isCurrentlyPresent') && $entry->isCurrentlyPresent()) {
                    return '<span class="badge bg-success">Present</span>';
                }
                return '<span class="badge bg-secondary">Absent</span>';
            })
            ->escaped(false);

        CRUD::column('user')->type('relationship')->linkTo('user.show');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'email' => 'required|email',
        ]);

        // Personal Info
        CRUD::field('first_name')->tab('Personal Info');
        CRUD::field('last_name')->tab('Personal Info');
        CRUD::field('email')->type('email')->tab('Personal Info');
        CRUD::field('phone')->tab('Personal Info');
        CRUD::field('avatar')->type('image')->tab('Personal Info');

        // Workplace Info
        CRUD::field('department')->type('relationship')->tab('Workplace Info');
        CRUD::field('workplace')->type('relationship')->tab('Workplace Info');
        CRUD::field('manager')->type('relationship')->tab('Workplace Info');
        CRUD::field('workplace_enter_code')->tab('Workplace Info');

        // Legal Details
        CRUD::field('address')->type('textarea')->tab('Legal Details');
        CRUD::field('id_document_type')->type('select_from_array')->options(['CI' => 'CI', 'BI' => 'BI', 'Pasaport' => 'Pasaport'])->allows_null(true)->tab('Legal Details');
        CRUD::field('id_document_number')->tab('Legal Details');
        CRUD::field('personal_numeric_code')->label('CNP')->tab('Legal Details');

        // User Account
        CRUD::field('user')
            ->type('relationship')
            ->label('User Account')
            ->tab('User Account')
            ->inline_create(true) // Requires InlineCreateOperation on UserCrudController
            ->ajax(true); // Requires FetchOperation on EmployeeCrudController
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        CRUD::column('first_name');
        CRUD::column('last_name');
        CRUD::column('email');
        CRUD::column('phone');
        CRUD::column('department');
        CRUD::column('workplace');
        CRUD::column('manager');
        CRUD::column('user');
        CRUD::column('address');
        CRUD::column('personal_numeric_code')->label('CNP');

        // Add widget for related tables
        \Backpack\CRUD\app\Library\Widget::add()
            ->to('after_content')
            ->type('view')
            ->view('admin.employee.show_inc.related_tables')
            ->entry($this->crud->getCurrentEntry());
    }

    protected function fetchUser()
    {
        return $this->fetch(\App\Models\User::class);
    }
}
