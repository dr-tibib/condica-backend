<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Workplace;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeamCommandCenterController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/team-command-center');
        CRUD::setEntityNameStrings('team command center', 'team command center');
    }

    public function setupListOperation()
    {
        // Eager loading
        $today = Carbon::today();
        $this->crud->with([
            'latestPresenceEvent.workplace',
            'department',
            'presenceEvents' => function($q) use ($today) {
                $q->where('event_time', '>=', $today)->orderBy('event_time');
            },
            'leaveRequests' => function($q) use ($today) {
                $q->where('status', 'APPROVED')
                  ->where('start_date', '<=', $today)
                  ->where('end_date', '>=', $today);
            }
        ]);

        // Columns
        // 1. Employee
        $this->crud->addColumn([
            'name' => 'employee_details',
            'label' => 'Employee',
            'type' => 'closure',
            'function' => function($entry) {
                $avatarUrl = $entry->avatar_url ?? '';
                $initial = substr($entry->name, 0, 1);
                $deptName = $entry->department->name ?? 'No Dept';

                return '
                <div class="d-flex py-1 align-items-center">
                    <span class="avatar me-2" style="background-image: url('.$avatarUrl.')">
                        '.$initial.'
                    </span>
                    <div class="flex-fill">
                        <div class="font-weight-medium">'.$entry->name.'</div>
                        <div class="text-muted"><a href="#" class="text-reset">'.$deptName.'</a></div>
                    </div>
                </div>';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('name', 'like', '%'.$searchTerm.'%');
                $query->orWhere('email', 'like', '%'.$searchTerm.'%');
            },
            'escaped' => false,
        ]);

        // 2. Live Status
        $this->crud->addColumn([
            'name' => 'live_status',
            'label' => 'Live Status',
            'type' => 'closure',
            'function' => function($entry) {
                $latestEvent = $entry->latestPresenceEvent;
                $approvedLeave = $entry->leaveRequests->first();

                $status = 'Absent';
                $statusClass = 'danger';

                if ($approvedLeave) {
                    $status = 'On Leave';
                    $statusClass = 'secondary';
                } elseif ($latestEvent) {
                    if ($latestEvent->event_type === 'check_in') {
                         if ($latestEvent->event_time->isToday()) {
                             $status = 'Active';
                             $statusClass = 'success';
                         }
                    } elseif ($latestEvent->event_type === 'delegation_start') {
                        $status = 'In Delegation';
                        $statusClass = 'primary';
                    }
                }

                return '<span class="badge bg-'.$statusClass.'-lt">● '.$status.'</span>';
            },
            'escaped' => false,
        ]);

        // 3. Location
        $this->crud->addColumn([
            'name' => 'location',
            'label' => 'Location',
            'type' => 'closure',
            'function' => function($entry) {
                $latestEvent = $entry->latestPresenceEvent;
                $approvedLeave = $entry->leaveRequests->first();

                // Re-derive status to determine location
                $status = 'Absent';
                if ($approvedLeave) {
                    $status = 'On Leave';
                } elseif ($latestEvent) {
                    if ($latestEvent->event_type === 'check_in') {
                        if ($latestEvent->event_time->isToday()) {
                            $status = 'Active';
                        }
                    } elseif ($latestEvent->event_type === 'delegation_start') {
                        $status = 'In Delegation';
                    }
                }

                $location = '-';
                if ($status === 'Active' && $latestEvent && $latestEvent->workplace) {
                    $location = $latestEvent->workplace->name;
                } elseif ($status === 'In Delegation') {
                    $location = 'Delegation Site';
                } elseif ($status === 'Active' && $latestEvent && !$latestEvent->workplace_id) {
                    $location = 'Remote';
                }

                return $location;
            }
        ]);

        // 4. Shift
        $this->crud->addColumn([
            'name' => 'shift',
            'label' => 'Shift',
            'type' => 'closure',
            'function' => function($entry) {
                return '<span class="text-muted">09:00 - 17:00</span>';
            },
            'escaped' => false,
        ]);

        // 5. Actual Hours
        $this->crud->addColumn([
            'name' => 'actual_hours',
            'label' => 'Actual Hours',
            'type' => 'closure',
            'function' => function($entry) {
                $eventsToday = $entry->presenceEvents;
                $minutes = $this->calculateMinutesFromEvents($eventsToday);
                $hours = floor($minutes / 60);
                $min = $minutes % 60;
                return sprintf('%dh %02dm', $hours, $min);
            }
        ]);

        // 6. Trend
        $this->crud->addColumn([
            'name' => 'trend',
            'label' => 'Trend (7D)',
            'type' => 'closure',
            'function' => function($entry) {
                 return '<span class="text-success">100% On Time</span>';
            },
            'escaped' => false,
        ]);

        // Remove default operations/buttons that shouldn't be here
        $this->crud->removeButton('create');
        $this->crud->removeButton('update');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');

        // Also remove line buttons if any were added by default (ListOperation adds edit/delete/show usually)
        // Since we are not using standard columns that might trigger buttons, but CrudController might.
        $this->crud->removeAllButtons();
    }

    public function index()
    {
        // --------------------------
        // 1. Stats
        // --------------------------
        $today = Carbon::today();

        // On Shift: Users with a check_in today and no subsequent check_out
        $usersOnShiftCount = User::whereHas('latestPresenceEvent', function ($query) use ($today) {
            $query->where('event_type', 'check_in')
                  ->where('event_time', '>=', $today);
        })->count();

        // On Delegation: Users with delegation_start today or ongoing
        $usersOnDelegationCount = User::whereHas('latestPresenceEvent', function ($query) {
            $query->where('event_type', 'delegation_start');
        })->count();

        // Absent / Late: Active users who are NOT present today AND NOT on approved leave
        $totalActiveUsers = User::count();

        $usersWithPresenceOrLeaveCount = User::where(function($query) use ($today) {
            $query->whereHas('presenceEvents', function ($q) use ($today) {
                $q->where('event_time', '>=', $today);
            })->orWhereHas('leaveRequests', function($q) use ($today) {
                 $q->where('status', 'APPROVED')
                   ->where('start_date', '<=', $today)
                   ->where('end_date', '>=', $today);
            });
        })->count();

        $absentCount = $totalActiveUsers - $usersWithPresenceOrLeaveCount;

        // Upcoming Time-Off (Next 7 Days)
        $upcomingLeaveCount = LeaveRequest::where('status', 'APPROVED')
            ->where('start_date', '>=', $today)
            ->where('start_date', '<=', $today->copy()->addDays(7))
            ->count();

        $this->data['stats'] = [
            'on_shift' => $usersOnShiftCount,
            'on_delegation' => $usersOnDelegationCount,
            'absent' => $absentCount,
            'upcoming_leave' => $upcomingLeaveCount,
        ];

        // --------------------------
        // 3. Action Center
        // --------------------------
        // Time Off Requests
        $timeOffRequests = LeaveRequest::where('status', 'PENDING')
            ->with(['user', 'leaveType'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Delegation Requests (Placeholder)
        $delegationRequests = [];

        // Alerts
        // Understaffed: Simple check if users on shift < 4 (Target from mockup)
        $understaffed = false;
        $targetStaff = 4; // Configurable ideally
        $now = Carbon::now();
        if ($usersOnShiftCount < $targetStaff && $now->hour >= 9 && $now->hour < 17) {
            $understaffed = true;
        }

        // Overtime: Check users with > 8h today
        $users = User::query()->with([
            'presenceEvents' => function($q) use ($today) {
                $q->where('event_time', '>=', $today)->orderBy('event_time');
            }
        ])->get();

        $overtimeCount = $users->filter(function($u) {
            $events = $u->presenceEvents; // Already filtered for today
            $mins = $this->calculateMinutesFromEvents($events);
            return $mins > (8 * 60);
        })->count();

        $this->data['actions'] = [
            'time_off_requests' => $timeOffRequests,
            'delegation_requests' => $delegationRequests,
            'understaffed' => $understaffed,
            'target_staff' => $targetStaff,
            'current_staff' => $usersOnShiftCount,
            'overtime_count' => $overtimeCount,
        ];

        $this->data['title'] = 'Team Command Center';
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin') => backpack_url('dashboard'),
            'Team Command Center' => false,
        ];

        return view('admin.dashboard.team_command_center', $this->data);
    }

    private function calculateMinutesFromEvents($events)
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

        // If still checked in, calculate until now
        if ($currentCheckIn !== null) {
             $totalMinutes += (int) $currentCheckIn->event_time->diffInMinutes(Carbon::now());
        }

        return $totalMinutes;
    }
}
