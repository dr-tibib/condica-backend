<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Workplace;
use Backpack\CRUD\app\Http\Controllers\AdminController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeamCommandCenterController extends AdminController
{
    public function dashboard()
    {
        $request = request();
        $this->data['title'] = 'Team Command Center';
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin') => backpack_url('dashboard'),
            'Team Command Center' => false,
        ];

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
        // 2. Roster (Live List)
        // --------------------------
        $query = User::query()->with([
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

        if ($request->has('search')) {
            $term = $request->input('search');
            $query->where('name', 'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%');
        }

        $users = $query->get();

        $roster = $users->map(function ($user) {
            $latestEvent = $user->latestPresenceEvent;
            $eventsToday = $user->presenceEvents;
            $approvedLeave = $user->leaveRequests->first();

            // Determine Status
            $status = 'Absent';
            $statusClass = 'danger'; // Red
            $location = '-';

            if ($approvedLeave) {
                $status = 'On Leave';
                $statusClass = 'secondary'; // Grey
            } elseif ($latestEvent) {
                if ($latestEvent->event_type === 'check_in') {
                    if ($latestEvent->event_time->isToday()) {
                        $status = 'Active';
                        $statusClass = 'success'; // Green
                    }
                } elseif ($latestEvent->event_type === 'delegation_start') {
                    $status = 'In Delegation';
                    $statusClass = 'primary'; // Purple/Blue
                }
            }

            // Determine Location
            if ($status === 'Active' && $latestEvent && $latestEvent->workplace) {
                $location = $latestEvent->workplace->name;
            } elseif ($status === 'In Delegation') {
                $location = 'Delegation Site';
            } elseif ($status === 'Active' && $latestEvent && !$latestEvent->workplace_id) {
                $location = 'Remote';
            }

            // Calculate Actual Hours
            $minutes = $this->calculateMinutesFromEvents($eventsToday);
            $hours = floor($minutes / 60);
            $min = $minutes % 60;
            $actualHours = sprintf('%dh %02dm', $hours, $min);

            // Trend (Mocked)
            $trend = '100% On Time';
            $trendClass = 'success';

            // Shift (Mocked)
            $shift = '09:00 - 17:00';

            return [
                'user' => $user,
                'status' => $status,
                'status_class' => $statusClass,
                'location' => $location,
                'shift' => $shift,
                'actual_hours' => $actualHours,
                'trend' => $trend,
                'trend_class' => $trendClass,
            ];
        });

        $this->data['roster'] = $roster;

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
