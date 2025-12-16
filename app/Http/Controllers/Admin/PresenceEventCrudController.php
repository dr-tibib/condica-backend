<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Pro\Http\Controllers\Operations\FetchOperation;

/**
 * Class PresenceEventCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PresenceEventCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use FetchOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\PresenceEvent::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/presence-event');
        CRUD::setEntityNameStrings('presence event', 'presence events');

        // Read-only: disable create, update, and delete
        CRUD::denyAccess(['create', 'update', 'delete']);
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
        // Columns
        CRUD::column('user')->type('relationship')->label('User')->attribute('name');

        CRUD::column('workplace')->type('relationship')->label('Workplace')->attribute('name');

        CRUD::column('event_type')
            ->label('Event Type')
            ->type('closure')
            ->function(function ($entry) {
                $badge = $entry->event_type === 'check_in' ? 'bg-success' : 'bg-info';
                $text = ucfirst(str_replace('_', ' ', $entry->event_type));

                return '<span class="badge ' . $badge . ' text-white">' . $text . '</span>';
            })
            ->escaped(false);

        CRUD::column('event_time')->label('Time')->type('datetime')->format('Y-MM-DD HH:mm:ss');

        CRUD::column('method')
            ->label('Method')
            ->type('closure')
            ->function(function ($entry) {
                $badge = $entry->method === 'auto' ? 'bg-primary' : 'bg-secondary';

                return '<span class="badge ' . $badge . ' text-white">' . ucfirst($entry->method) . '</span>';
            })
            ->escaped(false);

        CRUD::column('duration')
            ->label('Duration')
            ->type('closure')
            ->function(function ($entry) {
                $duration = $entry->getDurationMinutes();
                if ($duration === null) {
                    return '-';
                }
                $hours = floor($duration / 60);
                $minutes = $duration % 60;

                return $hours . 'h ' . $minutes . 'm';
            });

        CRUD::column('pair_event')
            ->label('Paired Event')
            ->type('closure')
            ->function(function ($entry) {
                if ($entry->pair_event_id) {
                    return '<a href="' . backpack_url('presence-event/' . $entry->pair_event_id . '/show') . '" class="badge bg-warning">View Paired</a>';
                }

                return '-';
            })
            ->escaped(false);

        // Filters
        CRUD::filter('user')
            ->label('User')
            ->type('select2')
            ->values(function () {
                return \App\Models\User::all()->pluck('name', 'id')->toArray();
            })
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'user_id', $value);
            });

        CRUD::filter('workplace')
            ->label('Workplace')
            ->type('select2')
            ->values(function () {
                return \App\Models\Workplace::all()->pluck('name', 'id')->toArray();
            })
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'workplace_id', $value);
            });

        CRUD::filter('event_type')
            ->label('Event Type')
            ->type('select2')
            ->values([
                'check_in' => 'Check In',
                'check_out' => 'Check Out',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'event_type', $value);
            });

        CRUD::filter('method')
            ->label('Method')
            ->type('select2')
            ->values([
                'auto' => 'Automatic',
                'manual' => 'Manual',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'method', $value);
            });

        CRUD::filter('date_range')
            ->label('Date Range')
            ->type('date_range')
            ->whenActive(function ($values) {
                $dates = json_decode($values);
                CRUD::addClause('whereDate', 'event_time', '>=', $dates->from);
                CRUD::addClause('whereDate', 'event_time', '<=', $dates->to);
            });

        // Default order
        CRUD::orderBy('event_time', 'desc');

        // Add export button
        CRUD::button('export')->stack('top')->view('crud::buttons.export');
    }

    /**
     * Export to CSV
     */
    public function export()
    {
        $entries = CRUD::getEntries();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="presence-events-' . date('Y-m-d-His') . '.csv"',
        ];

        $callback = function () use ($entries) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, ['ID', 'User', 'Workplace', 'Event Type', 'Event Time', 'Method', 'Duration (minutes)', 'Latitude', 'Longitude', 'Notes']);

            // Data
            foreach ($entries as $entry) {
                fputcsv($file, [
                    $entry->id,
                    $entry->user->name,
                    $entry->workplace->name,
                    $entry->event_type,
                    $entry->event_time->format('Y-m-d H:i:s'),
                    $entry->method,
                    $entry->getDurationMinutes() ?? '',
                    $entry->latitude ?? '',
                    $entry->longitude ?? '',
                    $entry->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
