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
        CRUD::setEntityNameStrings(__('presence event'), __('presence events'));

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
        CRUD::column('employee')->type('relationship')->label(__('Employee'))->attribute('name');

        CRUD::column('workplace')->type('relationship')->label(__('Workplace'))->attribute('name');

        CRUD::column('type')
            ->label(__('Type'))
            ->type('closure')
            ->function(function ($entry) {
                $badge = $entry->type === 'presence' ? 'bg-success' : 'bg-primary';
                return '<span class="badge ' . $badge . ' text-white">' . ucfirst($entry->type) . '</span>';
            })
            ->escaped(false);

        CRUD::column('start_at')->label(__('Start'))->type('datetime')->format('Y-MM-DD HH:mm:ss');
        CRUD::column('end_at')->label(__('End'))->type('datetime')->format('Y-MM-DD HH:mm:ss');

        CRUD::column('duration')
            ->label(__('Duration'))
            ->type('closure')
            ->function(function ($entry) {
                $duration = $entry->getDurationMinutes();
                if ($duration === null) {
                    if (!$entry->end_at && $entry->start_at->isToday()) {
                         $duration = (int) $entry->start_at->diffInMinutes(now());
                         return $duration . 'm (active)';
                    }
                    return '-';
                }
                $hours = floor($duration / 60);
                $minutes = $duration % 60;

                return $hours . 'h ' . $minutes . 'm';
            });

        // Filters
        CRUD::filter('employee')
            ->label(__('Employee'))
            ->type('select2')
            ->values(function () {
                return \App\Models\Employee::all()->pluck('name', 'id')->toArray();
            })
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'employee_id', $value);
            });

        CRUD::filter('type')
            ->label(__('Type'))
            ->type('select2')
            ->values([
                'presence' => __('Presence'),
                'delegation' => __('Delegation'),
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'type', $value);
            });

        CRUD::filter('date_range')
            ->label(__('Date Range'))
            ->type('date_range')
            ->whenActive(function ($values) {
                $dates = json_decode($values);
                CRUD::addClause('where', function($query) use ($dates) {
                    $query->whereBetween('start_at', [$dates->from, $dates->to])
                          ->orWhereBetween('end_at', [$dates->from, $dates->to]);
                });
            });

        // Default order
        CRUD::orderBy('start_at', 'desc');

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
            fputcsv($file, [
                __('ID'),
                __('Employee'),
                __('Workplace'),
                __('Type'),
                __('Start'),
                __('End'),
                __('Duration (minutes)'),
                __('Notes')
            ]);

            // Data
            foreach ($entries as $entry) {
                fputcsv($file, [
                    $entry->id,
                    $entry->employee->name,
                    $entry->workplace->name ?? '-',
                    $entry->type,
                    $entry->start_at->format('Y-m-d H:i:s'),
                    $entry->end_at ? $entry->end_at->format('Y-m-d H:i:s') : '',
                    $entry->getDurationMinutes() ?? '',
                    $entry->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
