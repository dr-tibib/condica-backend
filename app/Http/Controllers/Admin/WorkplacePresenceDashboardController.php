<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\Widget;
use App\Models\Employee;
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
        $this->crud->setModel(Employee::class);
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
            $currentlyWorkingCount = Employee::whereHas('presenceEvents', function ($query) {
                $query->where('type', 'presence')->active();
            })->count();

            // Widget: Active in Selected Range
            $activeInRangeCount = Employee::whereHas('presenceEvents', function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_at', [$startDate, $endDate]);
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
            $query->whereBetween('start_at', [$startDate, $endDate])
                  ->orderBy('start_at', 'asc');
        }]);

        // Also eager load department and workplace
        $this->crud->query->with(['department', 'workplace']);

        // 4. Add Filter
        if ($this->crud->filters()->where('name', 'date_range')->isEmpty()) {
            $this->crud->addFilter([
                'type'  => 'date_range',
                'name'  => 'date_range',
                'label' => __('Date Range'),
            ],
            false,
            function ($value) { // if the filter is active
            });
        }

        // 5. Columns
        $this->crud->addColumn([
            'name' => 'employee_details',
            'label' => __('Employee'),
            'type' => 'closure',
            'function' => function($entry) {
                 return $entry->name;
            },
             'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('first_name', 'like', '%'.$searchTerm.'%');
                $query->orWhere('last_name', 'like', '%'.$searchTerm.'%');
            }
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
                $first = $entry->presenceEvents->where('type', 'presence')->first();
                return $first ? $first->start_at->format('H:i') : '-';
            }
        ]);

        $this->crud->addColumn([
            'name' => 'last_check_out',
            'label' => __('Last Check Out'),
            'type' => 'closure',
            'function' => function($entry) {
                $last = $entry->presenceEvents->where('type', 'presence')->last();
                return $last && $last->end_at ? $last->end_at->format('H:i') : '-';
            }
        ]);

        $this->crud->addColumn([
            'name' => 'total_time',
            'label' => __('Total Time'),
            'type' => 'closure',
            'function' => function($entry) {
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

        foreach ($events as $event) {
            if ($event->end_at) {
                $totalMinutes += (int) $event->start_at->diffInMinutes($event->end_at);
            } elseif ($event->start_at->isToday()) {
                $totalMinutes += (int) $event->start_at->diffInMinutes(now());
            }
        }

        if ($totalMinutes == 0) return '-';

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%dh %02dm', $hours, $minutes);
    }
}
