<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

/**
 * Class TenantCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TenantCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Tenant::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/tenant');
        CRUD::setEntityNameStrings(__('central.tenant'), __('central.tenants'));

        // Ensure we can only manage tenants from central context (assumed by route definition location)
    }

    protected function setupListOperation()
    {
        CRUD::column('id')->label(__('central.tenant_id'));
        CRUD::column('company_name')->label(__('central.company_name'));
        CRUD::column('domains')
            ->label(__('central.domains'))
            ->type('relationship')
            ->attribute('domain');
        // display the number of users
        CRUD::column('users')->type('relationship_count')->label(__('central.central_users'));
    }

    protected function setupCreateOperation()
    {
        CRUD::field('id')->label(__('central.tenant_id'))->hint(__('central.tenant_id_hint'));
        CRUD::field('company_name')->label(__('central.company_name'));
        CRUD::field('logo')
            ->label(__('central.company_logo'))
            ->type('upload')
            ->withFiles([
                'disk' => 'public',
                'path' => 'tenant_logos',
            ]);

        CRUD::field('code_length')
            ->label(__('central.access_code_length'))
            ->type('number')
            ->default(3)
            ->attributes(['min' => 3, 'max' => 100])
            ->hint(__('central.access_code_length_hint'));

        // Add users relationship field?
        // Usually we assign users to tenants, or tenants to users.
        // Let's add a select2_multiple field for users
        CRUD::field('users')->label(__('central.assigned_users'))->type('relationship')->attribute('email');

        CRUD::field('domains')
            ->label(__('central.domains'))
            ->type('relationship')
            ->subfields([
                [
                    'name' => 'domain',
                    'type' => 'text',
                    'label' => __('central.domain_name'),
                ],
            ])
            ->reorderable(false)
            ->initRows(1)
            ->minRows(1);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::enableTabs();

        // Header Widget: Client Profile
        $entry = $this->crud->getCurrentEntry();
        Widget::add([
            'type'     => 'view',
            'view'     => 'admin.widgets.tenant_header',
            'tenant'   => $entry,
        ])->to('before_content');

        // Tab: Details
        CRUD::column('id')->label(__('central.tenant_id'))->tab(__('central.tenant'));
        CRUD::column('company_name')->label(__('central.company_name'))->tab(__('central.tenant'));
        CRUD::column('logo')->label(__('central.company_logo'))->type('image')->prefix('storage/')->tab(__('central.tenant'));

        // Tab: Infrastructure
        CRUD::column('domains')
            ->label(__('central.domains'))
            ->type('relationship')
            ->attribute('domain')
            ->tab(__('central.infrastructure'));

        CRUD::column('tenancy_db_name')
            ->label(__('central.database_name'))
            ->type('text')
            ->tab(__('central.infrastructure'));

        // Tab: Access
        CRUD::column('code_length')
            ->label(__('central.access_code_length'))
            ->type('number')
            ->tab(__('central.access_control'));

        CRUD::column('users')
            ->label(__('central.assigned_users'))
            ->type('relationship')
            ->attribute('email')
            ->tab(__('central.access_control'));

        // Tab: Audit
        CRUD::column('created_at')->label(__('central.created_at'))->tab(__('central.logs'));
        CRUD::column('updated_at')->label(__('central.updated_at'))->tab(__('central.logs'));
    }
}
