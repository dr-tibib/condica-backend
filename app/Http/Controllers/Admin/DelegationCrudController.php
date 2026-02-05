<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DelegationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DelegationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DelegationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Delegation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/delegation');
        CRUD::setEntityNameStrings('delegation', 'delegations');
    }

    protected function setupListOperation()
    {
        CRUD::column('user')->type('relationship')->label('User');

        CRUD::column('place_info')
            ->type('place_card')
            ->label('Place')
            ->escaped(false);

        CRUD::column('vehicle')->type('relationship')->label('Vehicle');

        CRUD::column('startEvent.event_time')
            ->type('datetime')
            ->label('Start Time');

        CRUD::column('endEvent.event_time')
            ->type('datetime')
            ->label('End Time');

        CRUD::column('created_at');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(DelegationRequest::class);

        CRUD::field('user')->type('relationship')->label('User');
        CRUD::field('delegationPlace')->type('relationship')->label('Delegation Place');
        CRUD::field('vehicle')->type('relationship')->label('Vehicle');

        // Fields for ad-hoc place editing if necessary
        CRUD::field('name')->label('Ad-hoc Place Name')->hint('Leave empty if selecting a Delegation Place');
        CRUD::field('address')->label('Ad-hoc Address');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
