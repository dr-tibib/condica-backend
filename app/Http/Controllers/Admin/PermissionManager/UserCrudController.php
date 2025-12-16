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

        $this->crud->addColumn([
            'name' => 'defaultWorkplace',
            'label' => 'Default Workplace',
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        $this->crud->addColumn([
            'name' => 'presence_status',
            'label' => 'Current Status',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->isCurrentlyPresent()) {
                    $workplace = $entry->getCurrentWorkplace();

                    return '<span class="badge bg-success">Present at ' . $workplace->name . '</span>';
                }

                return '<span class="badge bg-secondary">Not Present</span>';
            },
            'escaped' => false,
        ]);

        $this->crud->addButtonFromView('line', 'presence_history', 'presence_history', 'end');
    }

    public function setupCreateOperation()
    {
        parent::setupCreateOperation();

        $this->crud->addField([
            'name' => 'default_workplace_id',
            'label' => 'Default Workplace',
            'type' => 'relationship',
            'entity' => 'defaultWorkplace',
            'attribute' => 'name',
            'model' => \App\Models\Workplace::class,
            'tab' => 'Workplace',
        ]);

        $this->crud->addField([
            'name' => 'employee_id',
            'label' => 'Employee ID',
            'type' => 'text',
            'tab' => 'Workplace',
        ]);

        $this->crud->addField([
            'name' => 'department',
            'label' => 'Department',
            'type' => 'text',
            'tab' => 'Workplace',
        ]);

        $this->crud->addField([
            'name' => 'role',
            'label' => 'Job Role',
            'type' => 'text',
            'hint' => 'The user\'s position or job title',
            'tab' => 'Workplace',
        ]);
    }

    public function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
