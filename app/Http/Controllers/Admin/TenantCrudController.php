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
        CRUD::setEntityNameStrings('tenant', 'tenants');

        // Ensure we can only manage tenants from central context (assumed by route definition location)
    }

    protected function setupListOperation()
    {
        CRUD::column('id')->label('Tenant ID');
        // display the number of users
        CRUD::column('users')->type('relationship_count')->label('Users');
    }

    protected function setupCreateOperation()
    {
        CRUD::field('id')->label('Tenant ID (Subdomain/Domain ID)')->hint('Unique identifier for the tenant');
        // Add users relationship field?
        // Usually we assign users to tenants, or tenants to users.
        // Let's add a select2_multiple field for users
        CRUD::field('users')->label('Assigned Users')->type('relationship')->attribute('email');

        CRUD::field('domains')
            ->label('Domains')
            ->type('relationship')
            ->subfields([
                [
                    'name' => 'domain',
                    'type' => 'text',
                    'label' => 'Domain Name',
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
