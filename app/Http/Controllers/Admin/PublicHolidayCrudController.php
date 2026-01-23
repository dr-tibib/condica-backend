<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PublicHolidayCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\PublicHoliday::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/public-holiday');
        CRUD::setEntityNameStrings('public holiday', 'public holidays');
    }

    protected function setupListOperation()
    {
        CRUD::column('date')->type('date');
        CRUD::column('description');
    }

    protected function setupCreateOperation()
    {
        CRUD::field('date')->type('date');
        CRUD::field('description');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
