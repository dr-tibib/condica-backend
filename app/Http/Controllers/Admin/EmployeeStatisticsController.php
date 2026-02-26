<?php

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Services\LegacyReportService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceSheetExport;

/**
 * Class EmployeeStatisticsController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EmployeeStatisticsController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Employee::class);
        CRUD::setRoute(backpack_url('employee-statistics'));
        CRUD::setEntityNameStrings('statistică angajat', 'statistici angajați');
        
        $this->crud->denyAccess(['create', 'update', 'delete']);
    }

    public function downloadCondica(Request $request, LegacyReportService $reportService)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));

        return $reportService->downloadWithTemplate($year, $month);
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')
            ->label('Nume Angajat')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('first_name', 'like', '%'.$searchTerm.'%')
                      ->orWhere('last_name', 'like', '%'.$searchTerm.'%');
            })
            ->orderLogic(function ($query, $column, $columnDirection) {
                $query->orderBy('last_name', $columnDirection)
                      ->orderBy('first_name', $columnDirection);
            })
            ->orderable(true);

        CRUD::column('department.name')
            ->label('Departament')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhereHas('department', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            })
            ->orderable(true);

        // Date range filter
        $this->crud->addFilter([
            'type'  => 'date_range',
            'name'  => 'from_to',
            'label' => 'Perioadă'
        ],
        false,
        function ($value) { // if the filter is active
            $dates = json_decode($value);
        });

        $dates = $this->getDateRange();
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        // Lucrate (Presence)
        CRUD::column('worked_hours')
            ->label('Lucrate')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->formatMinutes($this->getMinutesByType($entry, 'presence', $startDate, $endDate));
            })
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, start_at, end_at) / 60)), 0)
                    FROM presence_events
                    WHERE employee_id = employees.id AND type = 'presence'
                    AND start_at BETWEEN '$start' AND '$end' AND end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Concediu
        CRUD::column('holiday_hours')
            ->label('Concediu')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->formatMinutes($this->getMinutesByLeaveType($entry, 'Concediu Odihnă', $startDate, $endDate));
            })
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, p.start_at, p.end_at) / 60)), 0)
                    FROM presence_events p
                    JOIN leave_requests lr ON p.linkable_id = lr.id AND p.linkable_type = 'App\\\\Models\\\\LeaveRequest'
                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                    WHERE p.employee_id = employees.id AND p.type = 'leave'
                    AND lt.name LIKE '%Concediu Odihnă%'
                    AND p.start_at BETWEEN '$start' AND '$end' AND p.end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Învoire
        CRUD::column('permit_hours')
            ->label('Învoire')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->formatMinutes($this->getMinutesByLeaveType($entry, 'Învoire', $startDate, $endDate));
            })
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, p.start_at, p.end_at) / 60)), 0)
                    FROM presence_events p
                    JOIN leave_requests lr ON p.linkable_id = lr.id AND p.linkable_type = 'App\\\\Models\\\\LeaveRequest'
                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                    WHERE p.employee_id = employees.id AND p.type = 'leave'
                    AND lt.name LIKE '%Învoire%'
                    AND p.start_at BETWEEN '$start' AND '$end' AND p.end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Concediu Medical
        CRUD::column('medical_hours')
            ->label('C. Medical')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->formatMinutes($this->getMinutesByLeaveType($entry, 'Concediu Medical', $startDate, $endDate));
            })
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, p.start_at, p.end_at) / 60)), 0)
                    FROM presence_events p
                    JOIN leave_requests lr ON p.linkable_id = lr.id AND p.linkable_type = 'App\\\\Models\\\\LeaveRequest'
                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                    WHERE p.employee_id = employees.id AND p.type = 'leave'
                    AND lt.name LIKE '%Concediu Medical%'
                    AND p.start_at BETWEEN '$start' AND '$end' AND p.end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Concediu Vechi
        CRUD::column('holiday_past_hours')
            ->label('C. Vechi')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->formatMinutes($this->getMinutesByLeaveType($entry, 'Concediu Vechi', $startDate, $endDate));
            })
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, p.start_at, p.end_at) / 60)), 0)
                    FROM presence_events p
                    JOIN leave_requests lr ON p.linkable_id = lr.id AND p.linkable_type = 'App\\\\Models\\\\LeaveRequest'
                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                    WHERE p.employee_id = employees.id AND p.type = 'leave'
                    AND lt.name LIKE '%Concediu Vechi%'
                    AND p.start_at BETWEEN '$start' AND '$end' AND p.end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Delegație
        CRUD::column('delegation_hours')
            ->label('Delegație')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->formatMinutes($this->getMinutesByType($entry, 'delegation', $startDate, $endDate));
            })
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, start_at, end_at) / 60)), 0)
                    FROM presence_events
                    WHERE employee_id = employees.id AND type = 'delegation'
                    AND start_at BETWEEN '$start' AND '$end' AND end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Total Ore
        CRUD::column('total_hours')
            ->label('Total Ore')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                $total = $this->getMinutesByType($entry, ['presence', 'delegation'], $startDate, $endDate)
                       + $this->getTotalLeaveMinutes($entry, $startDate, $endDate);
                return $this->formatMinutes($total);
            })
            ->wrapper([
                'title' => 'Suma tuturor orelor (Prezență + Delegație + Toate tipurile de concediu/învoire)',
            ])
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, start_at, end_at) / 60)), 0)
                    FROM presence_events
                    WHERE employee_id = employees.id
                    AND start_at BETWEEN '$start' AND '$end' AND end_at IS NOT NULL
                ) $columnDirection");
            })
            ->orderable(true);

        // Zile Delegație
        CRUD::column('delegation_days')
            ->label('Zile Delegație')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                return $this->getDelegationDaysCount($entry, $startDate, $endDate);
            })
            ->wrapper([
                'title' => 'Numărul de zile în care angajatul a fost în delegație',
            ])
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COUNT(DISTINCT DATE(start_at))
                    FROM presence_events
                    WHERE employee_id = employees.id AND type = 'delegation'
                    AND start_at BETWEEN '$start' AND '$end'
                ) $columnDirection");
            })
            ->orderable(true);

        // Pauză
        CRUD::column('pause_hours')
            ->label('Pauză')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                $days = $this->getActiveDaysCount($entry, $startDate, $endDate);
                return $this->formatMinutes($days * 30);
            })
            ->wrapper([
                'title' => 'Pauza de masă (30 min / zi lucrată sau în delegație)',
            ])
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    SELECT COUNT(DISTINCT DATE(start_at))
                    FROM presence_events
                    WHERE employee_id = employees.id AND type IN ('presence', 'delegation')
                    AND start_at BETWEEN '$start' AND '$end'
                ) $columnDirection");
            })
            ->orderable(true);

        // Ore Lucrate (Net)
        CRUD::column('net_hours')
            ->label('Ore Lucrate')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                $total = $this->getMinutesByType($entry, ['presence', 'delegation'], $startDate, $endDate)
                       + $this->getTotalLeaveMinutes($entry, $startDate, $endDate);
                $days = $this->getActiveDaysCount($entry, $startDate, $endDate);
                return $this->formatMinutes($total - ($days * 30));
            })
            ->wrapper([
                'title' => 'Total Ore - Pauză',
            ])
            ->orderLogic(function ($query, $column, $columnDirection) use ($startDate, $endDate) {
                $start = $startDate->format('Y-m-d H:i:s');
                $end = $endDate->format('Y-m-d H:i:s');
                $query->orderByRaw("(
                    (SELECT COALESCE(SUM(FLOOR(TIMESTAMPDIFF(SECOND, start_at, end_at) / 60)), 0)
                     FROM presence_events
                     WHERE employee_id = employees.id
                     AND start_at BETWEEN '$start' AND '$end' AND end_at IS NOT NULL)
                    - 
                    (SELECT COUNT(DISTINCT DATE(start_at)) * 30
                     FROM presence_events
                     WHERE employee_id = employees.id AND type IN ('presence', 'delegation')
                     AND start_at BETWEEN '$start' AND '$end')
                ) $columnDirection");
            })
            ->orderable(true);

        // Erori
        CRUD::column('errors')
            ->label('Erori')
            ->type('closure')
            ->function(function($entry) use ($startDate, $endDate) {
                $count = $entry->presenceEvents()
                    ->whereBetween('start_at', [$startDate, $endDate])
                    ->whereNull('end_at')
                    ->count();
                return $count > 0 ? '<span class="text-danger font-weight-bold">'.$count.'</span>' : '-';
            })
            ->orderable(true)
            ->escaped(false);

        $this->crud->addButtonFromView('line', 'view_presence', 'view_presence', 'beginning');
        $this->crud->addButtonFromView('top', 'download_condica', 'download_condica', 'beginning');
    }

    protected function setupShowOperation()
    {
        $this->crud->setShowView('admin.employee_statistics.show');
        
        $entry = $this->crud->getCurrentEntry();
        $dates = $this->getDateRange();
        
        $presenceEvents = $entry->presenceEvents()
            ->whereBetween('start_at', [$dates['start'], $dates['end']])
            ->orderBy('start_at')
            ->get();

        $this->data['presenceEvents'] = $presenceEvents;
        $this->data['startDate'] = $dates['start'];
        $this->data['endDate'] = $dates['end'];
    }

    private function getDateRange()
    {
        $range = request()->get('from_to');
        if ($range) {
            $range = json_decode($range);
            return [
                'start' => Carbon::parse($range->from)->startOfDay(),
                'end' => Carbon::parse($range->to)->endOfDay(),
            ];
        }

        return [
            'start' => now()->startOfMonth(),
            'end' => now()->endOfMonth(),
        ];
    }

    private function getMinutesByType($employee, $type, $start, $end)
    {
        $types = is_array($type) ? $type : [$type];
        $events = $employee->presenceEvents()
            ->whereIn('type', $types)
            ->whereBetween('start_at', [$start, $end])
            ->whereNotNull('end_at')
            ->get();

        $totalMinutes = 0;
        foreach ($events as $event) {
            $totalMinutes += (int) floor($event->start_at->diffInSeconds($event->end_at) / 60);
        }
        return $totalMinutes;
    }

    private function getMinutesByLeaveType($employee, $leaveTypeName, $start, $end)
    {
        $events = $employee->presenceEvents()
            ->where('type', 'leave')
            ->whereBetween('start_at', [$start, $end])
            ->whereHasMorph('linkable', [\App\Models\LeaveRequest::class], function($query) use ($leaveTypeName) {
                $query->whereHas('leaveType', function($q) use ($leaveTypeName) {
                    $q->where('name', 'like', '%' . $leaveTypeName . '%');
                });
            })
            ->get();

        $totalMinutes = 0;
        foreach ($events as $event) {
            if ($event->end_at) {
                $totalMinutes += (int) floor($event->start_at->diffInSeconds($event->end_at) / 60);
            }
        }
        return $totalMinutes;
    }

    private function getTotalLeaveMinutes($employee, $start, $end)
    {
        $events = $employee->presenceEvents()
            ->where('type', 'leave')
            ->whereBetween('start_at', [$start, $end])
            ->get();

        $totalMinutes = 0;
        foreach ($events as $event) {
            if ($event->end_at) {
                $totalMinutes += (int) floor($event->start_at->diffInSeconds($event->end_at) / 60);
            }
        }
        return $totalMinutes;
    }

    private function getActiveDaysCount($employee, $start, $end)
    {
        return $employee->presenceEvents()
            ->whereIn('type', ['presence', 'delegation'])
            ->whereBetween('start_at', [$start, $end])
            ->selectRaw('DATE(start_at) as date')
            ->distinct()
            ->get()
            ->count();
    }

    private function getDelegationDaysCount($employee, $start, $end)
    {
        return $employee->presenceEvents()
            ->where('type', 'delegation')
            ->whereBetween('start_at', [$start, $end])
            ->selectRaw('DATE(start_at) as date')
            ->distinct()
            ->get()
            ->count();
    }

    private function formatMinutes($totalMinutes)
    {
        if ($totalMinutes == 0) return '-';
        $isNegative = $totalMinutes < 0;
        $totalMinutes = abs($totalMinutes);
        $hours = (int) floor($totalMinutes / 60);
        $minutes = (int) ($totalMinutes % 60);
        return ($isNegative ? '-' : '') . sprintf('%d:%02d', $hours, $minutes);
    }
}
