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
    private bool $isCentralTenant = false;

    public function setup(): void
    {
        $this->isCentralTenant = tenancy()->tenant === null;
        parent::setup();
    }

    public function setupListOperation(): void
    {
        parent::setupListOperation();

        $this->crud->addColumn([
            'name' => 'is_global_superadmin',
            'label' => 'Global Superadmin',
            'type' => 'check',
        ]);

        if (!$this->isCentralTenant) {
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
    }

    public function setupCreateOperation(): void
    {
        parent::setupCreateOperation();

        if (!$this->isCentralTenant) {
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
                'type' => 'relationship',
                'entity' => 'department',
                'attribute' => 'name',
                'model' => \App\Models\Department::class,
                'tab' => 'Workplace',
            ]);

            $this->crud->addField([
                'name' => 'workplace_enter_code',
                'label' => 'Workplace Enter Code',
                'type' => 'text',
                'tab' => 'Workplace',
            ]);

            // Romanian Legal Fields
            $this->crud->addField([
                'name' => 'address',
                'label' => 'Address',
                'type' => 'textarea',
                'tab' => 'Legal Details',
            ]);

            $this->crud->addField([
                'name' => 'id_document_type',
                'label' => 'ID Document Type',
                'type' => 'select_from_array',
                'options' => ['CI' => 'CI', 'BI' => 'BI', 'Pasaport' => 'Pasaport'],
                'allows_null' => true,
                'tab' => 'Legal Details',
            ]);

            $this->crud->addField([
                'name' => 'id_document_number',
                'label' => 'ID Document Number',
                'type' => 'text',
                'tab' => 'Legal Details',
            ]);

            $this->crud->addField([
                'name' => 'personal_numeric_code',
                'label' => 'Personal Numeric Code (CNP)',
                'type' => 'text',
                'tab' => 'Legal Details',
            ]);

            $this->crud->addField([
                'name' => 'role',
                'label' => 'Job Role',
                'type' => 'text',
                'hint' => 'The user\'s position or job title',
                'tab' => 'Workplace',
            ]);
        }
    }

    public function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
