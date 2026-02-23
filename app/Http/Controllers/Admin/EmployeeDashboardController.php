<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Services\Dashboard\AlertService;
use Backpack\CRUD\app\Http\Controllers\AdminController;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmployeeDashboardController extends AdminController
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    public function dashboard()
    {
        $this->data['title'] = trans('backpack::base.dashboard');
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin')     => backpack_url('dashboard'),
            trans('backpack::base.dashboard') => false,
        ];

        /** @var User $user */
        $user = backpack_user();

        if (! $user->employee) {
            return view('admin.errors.no_employee_profile', $this->data);
        }

        $employee = $user->employee;

        // --------------------------
        // 1. Hero Metrics (Month)
        // --------------------------
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $now = Carbon::now();

        // Logged Hours
        $monthEvents = $employee->presenceEvents()
            ->where(function($query) use ($startOfMonth, $now) {
                $query->whereBetween('start_at', [$startOfMonth, $now])
                      ->orWhereBetween('end_at', [$startOfMonth, $now]);
            })
            ->orderBy('start_at')
            ->get();

        $loggedMinutes = $this->calculateMinutesFromEvents($monthEvents);
        $loggedHours = round($loggedMinutes / 60, 1);

        // Expected Hours
        $workingDaysInMonth = 0;
        $workingDaysToDate = 0;

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        foreach ($period as $date) {
            if ($date->isWeekday()) {
                $workingDaysInMonth++;
                if ($date->lt($now->startOfDay())) {
                    $workingDaysToDate++;
                } elseif ($date->isToday()) {
                    $workingDaysToDate++;
                }
            }
        }

        $expectedHoursMonth = $workingDaysInMonth * 8;
        $expectedHoursToDate = $workingDaysToDate * 8;

        // Overtime Balance
        $overtimeHours = $loggedHours - $expectedHoursToDate;

        $monthProgressPct = $expectedHoursMonth > 0 ? ($loggedHours / $expectedHoursMonth) * 100 : 0;
        $targetProgressPct = $expectedHoursMonth > 0 ? ($expectedHoursToDate / $expectedHoursMonth) * 100 : 0;

        $this->data['metrics'] = [
            'logged_hours' => $loggedHours,
            'expected_hours_month' => $expectedHoursMonth,
            'overtime_hours' => round($overtimeHours, 1),
            'month_progress_pct' => $monthProgressPct,
            'target_progress_pct' => $targetProgressPct,
        ];

        // --------------------------
        // 2. Recent Activity
        // --------------------------
        $recentStart = Carbon::today()->subDays(4);
        $recentEvents = $employee->presenceEvents()
            ->where('start_at', '>=', $recentStart)
            ->orderBy('start_at', 'desc')
            ->get()
            ->groupBy(function($event) {
                return $event->start_at->format('Y-m-d');
            });

        $activityLog = [];
        foreach ($recentEvents as $dateStr => $events) {
            $sortedEvents = $events->sortBy('start_at');
            $dailyMinutes = 0;
            foreach ($sortedEvents as $event) {
                if ($event->end_at) {
                    $dailyMinutes += (int) $event->start_at->diffInMinutes($event->end_at);
                } elseif ($event->start_at->isToday()) {
                    $dailyMinutes += (int) $event->start_at->diffInMinutes(now());
                }
            }

            $firstEvent = $sortedEvents->first();
            $lastEventWithEnd = $sortedEvents->filter(fn($e) => $e->end_at)->last();
            $hasOngoing = $sortedEvents->contains(fn($e) => !$e->end_at && $e->start_at->isToday());

            $isRemote = false;
            $locationName = 'Office';

            if ($firstEvent) {
                if ($firstEvent->type === 'delegation') {
                    $isRemote = true;
                    $locationName = 'Delegation';
                } elseif ($firstEvent->workplace) {
                    $locationName = $firstEvent->workplace->name;
                    if (stripos($firstEvent->workplace->name, 'Home') !== false || stripos($firstEvent->workplace->name, 'Remote') !== false) {
                        $isRemote = true;
                    }
                } elseif (!$firstEvent->workplace_id) {
                    $isRemote = true;
                    $locationName = 'Remote';
                }
            }

            $activityLog[] = [
                'date' => Carbon::parse($dateStr),
                'minutes' => $dailyMinutes,
                'hours_str' => floor($dailyMinutes / 60) . 'h ' . ($dailyMinutes % 60) . 'm',
                'start_time' => $firstEvent ? $firstEvent->start_at->format('H:i') : '-',
                'end_time' => $hasOngoing ? 'Now' : ($lastEventWithEnd ? $lastEventWithEnd->end_at->format('H:i') : '-'),
                'is_remote' => $isRemote,
                'location_name' => $locationName,
            ];
        }
        $this->data['activity_log'] = $activityLog;

        // --------------------------
        // 3. Upcoming
        // --------------------------
        $this->data['next_holiday'] = PublicHoliday::where('date', '>=', Carbon::today())
            ->orderBy('date')
            ->first();

        $this->data['next_leave'] = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->where('start_date', '>=', Carbon::today())
            ->orderBy('start_date')
            ->first();

        // --------------------------
        // 4. Alerts
        // --------------------------
        $this->data['alerts'] = $this->alertService->getAlerts($employee);

        return view('admin.dashboard.employee', $this->data);
    }

    private function calculateMinutesFromEvents($events)
    {
        $totalMinutes = 0;
        foreach ($events as $event) {
            if ($event->end_at) {
                $totalMinutes += (int) $event->start_at->diffInMinutes($event->end_at);
            } elseif ($event->start_at->isToday()) {
                $totalMinutes += (int) $event->start_at->diffInMinutes(now());
            }
        }
        return $totalMinutes;
    }
}
