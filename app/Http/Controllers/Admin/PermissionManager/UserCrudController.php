<?php

namespace App\Http\Controllers\Admin\PermissionManager;

use Backpack\PermissionManager\app\Http\Controllers\UserCrudController as BaseUserCrudController;

/**
 * Class UserCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends BaseUserCrudController
{
    public function setup()
    {
        parent::setup();
    }

    public function setupListOperation()
    {
        parent::setupListOperation();

        $this->crud->addColumn([
            'name' => 'is_global_superadmin',
            'label' => 'Global Superadmin',
            'type' => 'check',
        ]);
    }
}
