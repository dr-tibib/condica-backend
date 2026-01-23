<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

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
        CRUD::setRoute(config('backpack.base.route_prefix').'/tenant');
        CRUD::setEntityNameStrings(__('tenant'), __('tenants'));

        // Ensure we can only manage tenants from central context (assumed by route definition location)
    }

    protected function setupListOperation()
    {
        CRUD::column('id')->label(__('Tenant ID'));
        // display the number of users
        CRUD::column('users')->type('relationship_count')->label(__('Users'));
        CRUD::column('company_name')->label(__('Company Name'));
    }

    protected function setupCreateOperation()
    {
        CRUD::field('id')->label(__('Tenant ID (Subdomain/Domain ID)'))->hint(__('Unique identifier for the tenant'));
        CRUD::field('company_name')->label(__('Company Name'));
        CRUD::field('logo')
            ->label(__('Company Logo'))
            ->type('upload')
            ->withFiles([
                'disk' => 'public',
                'path' => 'tenant_logos',
            ]);

        CRUD::field('code_length')
            ->label(__('Access Code Length'))
            ->type('number')
            ->default(3)
            ->attributes(['min' => 3, 'max' => 10])
            ->hint(__('Length of the code used for kiosk entry (default: 3)'))
            ->fake(true)
            ->store_in('data');

        // Add users relationship field?
        // Usually we assign users to tenants, or tenants to users.
        // Let's add a select2_multiple field for users
        CRUD::field('users')->label(__('Assigned Users'))->type('relationship')->attribute('email');

        CRUD::field('domains')
            ->label(__('Domains'))
            ->type('relationship')
            ->subfields([
                [
                    'name' => 'domain',
                    'type' => 'text',
                    'label' => __('Domain Name'),
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
}
