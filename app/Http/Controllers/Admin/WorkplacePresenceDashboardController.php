<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\Widget;
use App\Models\User;
use Carbon\Carbon;

/**
 * Class WorkplacePresenceDashboardController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class WorkplacePresenceDashboardController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel(User::class);
        $this->crud->setRoute(backpack_url('workplace-presence'));
        $this->crud->setEntityNameStrings(__('workplace presence'), __('workplace presence'));

        // Deny all write operations
        $this->crud->denyAccess(['create', 'update', 'delete']);
    }

    public function setupListOperation()
    {
        // 1. Get Date Range from Request or Default to Today
        $dateRange = json_decode(request('date_range', '[]'), true);

        $startDate = isset($dateRange['from']) ? Carbon::parse($dateRange['from'])->startOfDay() : Carbon::today()->startOfDay();
        $endDate = isset($dateRange['to']) ? Carbon::parse($dateRange['to'])->endOfDay() : Carbon::today()->endOfDay();

        // 2. Add Widgets
        // Only run expensive queries if not AJAX (Dashboard view)
        if (! $this->crud->getRequest()->ajax()) {
            // Widget: Currently Working (Real-time)
            $currentlyWorkingCount = User::whereHas('latestPresenceEvent', function ($query) {
                $query->where('event_type', 'check_in');
            })->count();

            // Widget: Active in Selected Range
            $activeInRangeCount = User::whereHas('presenceEvents', function($q) use ($startDate, $endDate) {
                $q->whereBetween('event_time', [$startDate, $endDate]);
            })->count();

            Widget::add()->to('before_content')->type('div')->class('row')->content([
                Widget::make()
                    ->type('progress_white')
                    ->class('card mb-2')
                    ->value($currentlyWorkingCount)
                    ->description(__('Employees Currently Working'))
                    ->progress(100)
                    ->hint(__('Real-time status')),

                Widget::make()
                    ->type('progress_white')
                    ->class('card mb-2')
                    ->value($activeInRangeCount)
                    ->description(__('Employees Active in Range'))
                    ->progress(100)
                    ->hint(__('Based on selected date range')),
            ]);
        }

        // 3. Eager Load Presence Events for the Range to avoid N+1
        $this->crud->query->with(['presenceEvents' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('event_time', [$startDate, $endDate])
                  ->orderBy('event_time', 'asc');
        }]);

        // Also eager load department and workplace
        $this->crud->query->with(['department', 'defaultWorkplace']);

        // 4. Add Filter
        if ($this->crud->filters()->where('name', 'date_range')->isEmpty()) {
            $this->crud->addFilter([
                'type'  => 'date_range',
                'name'  => 'date_range',
                'label' => __('Date Range'),
            ],
            false,
            function ($value) { // if the filter is active
                // The filter logic is handled by the eager load modification above
                // We don't filter the *User* list itself (we show all users),
                // unless we want to hide users with no activity.
                // For now, let's show all users.
            });
        }

        // 5. Columns
        $this->crud->addColumn([
            'name' => 'name',
            'label' => __('Employee'),
            'type' => 'text',
        ]);

        $this->crud->addColumn([
            'name' => 'department_custom',
            'label' => __('Department'),
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->department?->name ?? '-';
            }
        ]);

        $this->crud->addColumn([
            'name' => 'first_check_in',
            'label' => __('First Check In'),
            'type' => 'closure',
            'function' => function($entry) {
                // $entry->presenceEvents is already filtered by eager load
                $first = $entry->presenceEvents->where('event_type', 'check_in')->first();
                return $first ? $first->event_time->format('H:i') : '-';
            }
        ]);

        $this->crud->addColumn([
            'name' => 'last_check_out',
            'label' => __('Last Check Out'),
            'type' => 'closure',
            'function' => function($entry) {
                $last = $entry->presenceEvents->where('event_type', 'check_out')->last();
                return $last ? $last->event_time->format('H:i') : '-';
            }
        ]);

        $this->crud->addColumn([
            'name' => 'total_time',
            'label' => __('Total Time'),
            'type' => 'closure',
            'function' => function($entry) {
                // Calculate total time from filtered events
                // This logic needs to match User::calculateMinutesFromEvents but for the collection
                return $this->calculateDurationString($entry->presenceEvents);
            }
        ]);

        $this->crud->addColumn([
            'name' => 'status',
            'label' => __('Status'),
            'type' => 'closure',
            'function' => function($entry) use ($startDate, $endDate) {
                 if ($entry->presenceEvents->isEmpty()) {
                     return '<span class="badge bg-secondary">' . __('Absent') . '</span>';
                 }

                 // If looking at Today, check real-time status
                 if ($endDate->isToday() && $entry->isCurrentlyPresent()) {
                     return '<span class="badge bg-success">' . __('Working Now') . '</span>';
                 }

                 return '<span class="badge bg-info">' . __('Present') . '</span>';
            },
            'escaped' => false,
        ]);
    }

    private function calculateDurationString($events)
    {
        $totalMinutes = 0;
        $currentCheckIn = null;

        foreach ($events as $event) {
            if ($event->event_type === 'check_in') {
                $currentCheckIn = $event;
            } elseif ($event->event_type === 'check_out' && $currentCheckIn !== null) {
                $totalMinutes += (int) $currentCheckIn->event_time->diffInMinutes($event->event_time);
                $currentCheckIn = null;
            }
        }

        if ($totalMinutes == 0) return '-';

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%dh %02dm', $hours, $minutes);
    }
}
