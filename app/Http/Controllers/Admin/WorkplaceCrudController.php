<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\WorkplaceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class WorkplaceCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class WorkplaceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Workplace::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/workplace');
        CRUD::setEntityNameStrings('workplace', 'workplaces');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Name');

        CRUD::column('location')->label('Location')->type('closure')->function(function ($entry) {
            if ($entry->latitude && $entry->longitude) {
                return number_format($entry->latitude, 6) . ', ' . number_format($entry->longitude, 6);
            }

            return '-';
        });

        CRUD::column('radius')->label('Radius (m)')->suffix(' m');

        CRUD::column('timezone')->label('Timezone');

        CRUD::column('wifi_ssid')->label('WiFi SSID');

        CRUD::column('is_active')->label('Active')->type('boolean');

        CRUD::column('currently_present')->label('Present Now')->type('closure')->function(function ($entry) {
            $count = $entry->currentlyPresentUsers()->count();

            return $count > 0 ? '<span class="badge bg-success">' . $count . ' present</span>' : '<span class="badge bg-secondary">None</span>';
        })->escaped(false);

        CRUD::column('created_at')->label('Created');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(WorkplaceRequest::class);

        CRUD::field('name')
            ->label('Workplace Name')
            ->type('text')
            ->attributes(['placeholder' => 'e.g. Main Office, Branch 1'])
            ->hint('A descriptive name for this workplace location');

        CRUD::field('location_map')
            ->label('Location')
            ->type('google_map')
            ->map_options([
                'default_lat' => 46.7712,
                'default_lng' => 23.6236,
                'height' => 400
            ])
            ->wrapper(['class' => 'form-group col-md-12']);

        CRUD::field('radius')
            ->label('Geofence Radius (meters)')
            ->type('range')
            ->attributes([
                'min' => 10,
                'max' => 1000,
                'step' => 10,
            ])
            ->default(100)
            ->hint('Distance in meters for check-in/check-out validation');

        CRUD::field('timezone')
            ->label('Timezone')
            ->type('select_from_array')
            ->options(array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()))
            ->allows_null(false)
            ->default('UTC')
            ->hint('Timezone for this workplace location');

        CRUD::field('wifi_ssid')
            ->label('WiFi SSID')
            ->type('text')
            ->attributes(['placeholder' => 'Company WiFi'])
            ->hint('Optional: WiFi network name for additional validation');

        CRUD::field('is_active')
            ->label('Active')
            ->type('switch')
            ->default(true)
            ->hint('Inactive workplaces cannot be used for check-ins');
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
