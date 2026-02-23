<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DelegationPlaceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

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

        CRUD::column('source')
            ->type('closure')
            ->label('Source')
            ->function(function($entry) {
                return $entry->metadata['sync_source'] ?? 'Manual';
            });

        CRUD::column('original_name')
            ->type('closure')
            ->label('Original Name')
            ->function(function($entry) {
                return $entry->metadata['original_name'] ?? '-';
            });

        CRUD::column('accuracy')
            ->type('closure')
            ->label('Accuracy')
            ->function(function($entry) {
                $rating = $entry->metadata['google_data']['rating'] ?? null;
                return $rating ? $rating . ' ⭐' : 'N/A';
            });

        CRUD::column('created_at');

        // Filters
        CRUD::addFilter([
            'name'  => 'source',
            'type'  => 'dropdown',
            'label' => 'Source'
        ], [
            'old_condica' => 'Old Condica Sync',
            'Manual' => 'Manual Entry',
        ], function($value) {
            if ($value === 'Manual') {
                CRUD::addClause('where', 'metadata', null);
            } else {
                CRUD::addClause('where', 'metadata->sync_source', $value);
            }
        });

        CRUD::addFilter([
            'name'  => 'has_google_id',
            'type'  => 'simple',
            'label' => 'Resolved via Google'
        ],
        false,
        function() {
            CRUD::addClause('whereNotNull', 'google_place_id');
        });
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::enableTabs();

        // Map Widget
        $entry = $this->crud->getCurrentEntry();
        if ($entry->latitude && $entry->longitude) {
            Widget::add([
                'type'     => 'view',
                'view'     => 'admin.widgets.place_map',
                'place'    => $entry,
            ])->to('before_content');
        }

        CRUD::column('photo_url')
            ->label('Place Photo')
            ->type('closure')
            ->function(function($entry) {
                if (!$entry->photo_url) return '-';
                return '<img src="'.$entry->photo_url.'" style="max-height:300px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            })
            ->escaped(false)
            ->tab('Details');
        
        CRUD::column('metadata')
            ->label('Full Metadata (Debug)')
            ->type('json')
            ->tab('Technical Data');
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
            ->map_options([
                'key' => config('services.google_places.key'),
                'default_lat' => 44.4268,
                'default_lng' => 26.1025,
                'lat' => 'latitude',
                'lng' => 'longitude',
                'google_place_id' => 'google_place_id',
                'formatted_address' => 'address',
                'photo_reference' => 'photo_reference',
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
