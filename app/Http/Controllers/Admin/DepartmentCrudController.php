<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Department;

/**
 * Class DepartmentCrudController
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DepartmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/department');
        CRUD::setEntityNameStrings(__('department'), __('departments'));
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name')->label(__('Name'));
        CRUD::column('description')->label(__('Description'));
        CRUD::column('created_at')->label(__('Created At'));
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation([
            'name' => 'required|min:2|max:255',
            'description' => 'nullable|string',
        ]);

        CRUD::field('name')->label(__('Name'));
        CRUD::field('description')->label(__('Description'))->type('textarea');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
