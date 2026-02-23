<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DomainRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DomainCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DomainCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Domain::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/domain');
        CRUD::setEntityNameStrings(__('central.domain_name'), __('central.domains'));
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('domain')->label(__('central.domain_name'));
        CRUD::column('tenant_id')
            ->label(__('central.tenant_id'))
            ->type('closure')
            ->function(function($entry) {
                return '<a href="'.backpack_url('tenant/'.$entry->tenant_id.'/show').'">'.$entry->tenant_id.'</a>';
            })
            ->escaped(false);
        
        CRUD::column('created_at')->label(__('central.created_at'));
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(DomainRequest::class);
        
        CRUD::field('domain')->label(__('central.domain_name'))->placeholder('e.g. oaksoft.test');
        CRUD::field('tenant_id')->type('relationship')->label(__('central.tenant'))->attribute('id');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
