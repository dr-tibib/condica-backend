<?php

namespace App\Http\Controllers\Admin\PermissionManager;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest as StoreRequest;
use Backpack\PermissionManager\app\Http\Requests\UserUpdateCrudRequest as UpdateRequest;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    private bool $isCentralTenant = false;

    public function setup(): void
    {
        $this->isCentralTenant = tenancy()->tenant === null;

        $this->crud->setModel(config('backpack.permissionmanager.models.user'));
        $this->crud->setEntityNameStrings(trans('backpack::permissionmanager.user'), trans('backpack::permissionmanager.users'));
        $this->crud->setRoute(backpack_url('user'));
    }

    public function setupListOperation(): void
    {
        // Base columns
        $this->crud->addColumns([
            [
                'name'  => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type'  => 'text',
            ],
            [
                'name'  => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type'  => 'email',
            ],
            [ // n-n relationship (with pivot table)
                'label'     => trans('backpack::permissionmanager.roles'), // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'roles', // the method that defines the relationship in your Model
                'entity'    => 'roles', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => config('permission.models.role'), // foreign key model
            ],
            [ // n-n relationship (with pivot table)
                'label'     => trans('backpack::permissionmanager.extra_permissions'), // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'permissions', // the method that defines the relationship in your Model
                'entity'    => 'permissions', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => config('permission.models.permission'), // foreign key model
            ],
        ]);

        // Custom columns
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
                    if (method_exists($entry, 'isCurrentlyPresent') && $entry->isCurrentlyPresent()) {
                        // Ensure getCurrentWorkplace exists and returns something
                        if (method_exists($entry, 'getCurrentWorkplace')) {
                            $workplace = $entry->getCurrentWorkplace();
                            return '<span class="badge bg-success">Present at ' . ($workplace->name ?? 'Unknown') . '</span>';
                        }
                    }

                    return '<span class="badge bg-secondary">Not Present</span>';
                },
                'escaped' => false,
            ]);

            $this->crud->addButtonFromView('line', 'presence_history', 'presence_history', 'end');
        }

        // Filters from base controller
        if (backpack_pro()) {
            // Role Filter
            $this->crud->addFilter(
                [
                    'name'  => 'role',
                    'type'  => 'dropdown',
                    'label' => trans('backpack::permissionmanager.role'),
                ],
                config('permission.models.role')::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'roles', function ($query) use ($value) {
                        $query->where('role_id', '=', $value);
                    });
                }
            );

            // Extra Permission Filter
            $this->crud->addFilter(
                [
                    'name'  => 'permissions',
                    'type'  => 'select2',
                    'label' => trans('backpack::permissionmanager.extra_permissions'),
                ],
                config('permission.models.permission')::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'permissions', function ($query) use ($value) {
                        $query->where('permission_id', '=', $value);
                    });
                }
            );
        }
    }

    public function setupCreateOperation(): void
    {
        $this->crud->setValidation(StoreRequest::class);
        $this->addUserFields();
    }

    public function setupUpdateOperation(): void
    {
        $this->crud->setValidation(UpdateRequest::class);
        $this->addUserFields();
    }

    protected function addUserFields()
    {
        // Tab: User Info
        $this->crud->addFields([
            [
                'name'  => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type'  => 'text',
                'tab'   => 'User Info',
            ],
            [
                'name'  => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type'  => 'email',
                'tab'   => 'User Info',
            ],
            [
                'name'  => 'password',
                'label' => trans('backpack::permissionmanager.password'),
                'type'  => 'password',
                'tab'   => 'User Info',
            ],
            [
                'name'  => 'password_confirmation',
                'label' => trans('backpack::permissionmanager.password_confirmation'),
                'type'  => 'password',
                'tab'   => 'User Info',
            ],
        ]);

        // Tab: Roles & Permissions
        $this->crud->addField([
            // two interconnected entities
            'label'             => trans('backpack::permissionmanager.user_role_permission'),
            'field_unique_name' => 'user_role_permission',
            'type'              => 'checklist_dependency',
            'name'              => 'roles,permissions',
            'tab'               => 'Roles & Permissions',
            'subfields'         => [
                'primary' => [
                    'label'            => trans('backpack::permissionmanager.roles'),
                    'name'             => 'roles', // the method that defines the relationship in your Model
                    'entity'           => 'roles', // the method that defines the relationship in your Model
                    'entity_secondary' => 'permissions', // the method that defines the relationship in your Model
                    'attribute'        => 'name', // foreign key attribute that is shown to user
                    'model'            => config('permission.models.role'), // foreign key model
                    'pivot'            => true, // on create&update, do you need to add/delete pivot table entries?]
                    'number_columns'   => 3, //can be 1,2,3,4,6
                ],
                'secondary' => [
                    'label'          => mb_ucfirst(trans('backpack::permissionmanager.permission_plural')),
                    'name'           => 'permissions', // the method that defines the relationship in your Model
                    'entity'         => 'permissions', // the method that defines the relationship in your Model
                    'entity_primary' => 'roles', // the method that defines the relationship in your Model
                    'attribute'      => 'name', // foreign key attribute that is shown to user
                    'model'          => config('permission.models.permission'), // foreign key model
                    'pivot'          => true, // on create&update, do you need to add/delete pivot table entries?]
                    'number_columns' => 3, //can be 1,2,3,4,6
                ],
            ],
        ]);

        // Tenant specific fields
        if (!$this->isCentralTenant) {
            // Tab: Workplace
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

             $this->crud->addField([
                'name' => 'role',
                'label' => 'Job Role',
                'type' => 'text',
                'hint' => 'The user\'s position or job title',
                'tab' => 'Workplace',
            ]);

            // Tab: Legal Details
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
        }
    }

    public function setupShowOperation()
    {
        // automatically add the columns
        $this->crud->column('name');
        $this->crud->column('email');

        $this->crud->addColumn([
            'label'     => trans('backpack::permissionmanager.roles'),
            'type'      => 'select_multiple',
            'name'      => 'roles',
            'entity'    => 'roles',
            'attribute' => 'name',
            'model'     => config('permission.models.role'),
        ]);

        $this->crud->addColumn([
            'label'     => trans('backpack::permissionmanager.extra_permissions'),
            'type'      => 'select_multiple',
            'name'      => 'permissions',
            'entity'    => 'permissions',
            'attribute' => 'name',
            'model'     => config('permission.models.permission'),
        ]);

        $this->crud->column('created_at');
        $this->crud->column('updated_at');
    }

    /**
     * Store a newly created resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run

        return $this->traitStore();
    }

    /**
     * Update the specified resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run

        return $this->traitUpdate();
    }

    /**
     * Handle password input fields.
     */
    protected function handlePasswordInput($request)
    {
        // Remove fields not present on the user.
        $request->request->remove('password_confirmation');
        $request->request->remove('roles_show');
        $request->request->remove('permissions_show');

        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        } else {
            $request->request->remove('password');
        }

        return $request;
    }
}
