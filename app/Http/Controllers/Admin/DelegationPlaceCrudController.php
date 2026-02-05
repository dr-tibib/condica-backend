<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DelegationPlaceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DelegationPlaceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DelegationPlaceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\DelegationPlace::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/delegation-place');
        CRUD::setEntityNameStrings('delegation place', 'delegation places');
    }

    protected function setupListOperation()
    {
        CRUD::column('place_info')
            ->type('place_card')
            ->label('Place')
            ->escaped(false);

        CRUD::column('created_at');
        CRUD::column('updated_at');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(DelegationPlaceRequest::class);

        CRUD::field('name')->label('Name')->wrapper(['class' => 'form-group col-md-12']);

        CRUD::field('address')->type('hidden');
        CRUD::field('latitude')->type('hidden');
        CRUD::field('longitude')->type('hidden');
        CRUD::field('google_place_id')->type('hidden');

        CRUD::field('map')
            ->type('google_map')
            ->label('Location (Search and Select)')
            ->options([
                'key' => config('services.google_places.key'),
                'default_lat' => 44.4268,
                'default_lng' => 26.1025,
                'lat' => 'latitude',
                'lng' => 'longitude',
                'google_place_id' => 'google_place_id',
                'formatted_address' => 'address',
            ])
            ->hint('Use the map to search for a location.');

        CRUD::field('photo_reference')
             ->type('text')
             ->label('Google Photo Reference')
             ->hint('Automatically populated if possible, or paste manually.')
             ->wrapper(['class' => 'form-group col-md-12']);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
