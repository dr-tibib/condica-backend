<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\WorkplaceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

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
        CRUD::setEntityNameStrings(__('workplace'), __('workplaces'));
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
        CRUD::column('name')->label(__('Name'));

        CRUD::column('location')->label(__('Location'))->type('closure')->function(function ($entry) {
            if ($entry->latitude && $entry->longitude) {
                return number_format($entry->latitude, 6) . ', ' . number_format($entry->longitude, 6);
            }

            return '-';
        });

        CRUD::column('radius')->label(__('Radius (m)'))->suffix(' m');

        CRUD::column('timezone')->label(__('Timezone'));

        CRUD::column('wifi_ssid')->label(__('WiFi SSID'));

        CRUD::column('is_active')->label(__('Active'))->type('boolean');

        CRUD::column('currently_present')->label(__('Present Now'))->type('closure')->function(function ($entry) {
            $count = $entry->currentlyPresentUsers()->count();

            return $count > 0 ? '<span class="badge bg-success">' . $count . ' ' . __('present') . '</span>' : '<span class="badge bg-secondary">' . __('None') . '</span>';
        })->escaped(false);

        CRUD::column('created_at')->label(__('Created'));
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
        Widget::add()->type('script')->content('assets/js/admin/workplace_map.js');

        CRUD::field('name')
            ->label(__('Workplace Name'))
            ->type('text')
            ->attributes(['placeholder' => __('e.g. Main Office, Branch 1')])
            ->hint(__('A descriptive name for this workplace location'));

        CRUD::field('street_address')->label(__('Street Address'))->type('text');
        CRUD::field('city')->label(__('City'))->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('county')->label(__('County'))->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('country')->label(__('Country'))->type('text');

        CRUD::field('location_map')
            ->label(__('Location'))
            ->type('google_map')
            ->map_options([
                'default_lat' => 46.7712,
                'default_lng' => 23.6236,
                'height' => 400
            ])
            ->wrapper(['class' => 'form-group col-md-12']);

        CRUD::field('radius')
            ->label(__('Geofence Radius (meters)'))
            ->type('range')
            ->attributes([
                'min' => 10,
                'max' => 1000,
                'step' => 10,
            ])
            ->default(100)
            ->hint(__('Distance in meters for check-in/check-out validation'));

        CRUD::field('timezone')
            ->label(__('Timezone'))
            ->type('select_from_array')
            ->options(array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()))
            ->allows_null(false)
            ->default('UTC')
            ->hint(__('Timezone for this workplace location'));

        CRUD::field('wifi_ssid')
            ->label(__('WiFi SSID'))
            ->type('text')
            ->attributes(['placeholder' => __('Company WiFi')])
            ->hint(__('Optional: WiFi network name for additional validation'));

        CRUD::field('is_active')
            ->label(__('Active'))
            ->type('switch')
            ->default(true)
            ->hint(__('Inactive workplaces cannot be used for check-ins'));
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
