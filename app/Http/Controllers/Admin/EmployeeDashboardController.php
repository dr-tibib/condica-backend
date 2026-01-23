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

        // --------------------------
        // 1. Hero Metrics (Month)
        // --------------------------
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $now = Carbon::now();

        // Logged Hours
        $monthEvents = $user->presenceEvents()
            ->whereBetween('event_time', [$startOfMonth, $now])
            ->orderBy('event_time')
            ->get();

        $loggedMinutes = $this->calculateMinutesFromEvents($monthEvents);
        $loggedHours = round($loggedMinutes / 60, 1);

        // Expected Hours
        // Assume 8h per weekday
        $workingDaysInMonth = 0;
        $workingDaysToDate = 0;

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        foreach ($period as $date) {
            if ($date->isWeekday()) {
                $workingDaysInMonth++;
                if ($date->lt($now->startOfDay())) {
                    $workingDaysToDate++;
                } elseif ($date->isToday()) {
                    // For today, we can count it as expected or partially expected.
                    // Let's count it as fully expected for the "Goal" logic usually.
                    $workingDaysToDate++;
                }
            }
        }

        $expectedHoursMonth = $workingDaysInMonth * 8;
        $expectedHoursToDate = $workingDaysToDate * 8;

        // Overtime Balance
        $overtimeHours = $loggedHours - $expectedHoursToDate;

        // Completion Projection (Frontend will use percentages)
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
        // Get events from last 5 days
        $recentStart = Carbon::today()->subDays(4);
        $recentEvents = $user->presenceEvents()
            ->where('event_time', '>=', $recentStart)
            ->orderBy('event_time', 'desc')
            ->get()
            ->groupBy(function($event) {
                return $event->event_time->format('Y-m-d');
            });

        $activityLog = [];
        foreach ($recentEvents as $dateStr => $events) {
            // Sort ascending for calculation
            $sortedEvents = $events->sortBy('event_time');
            $dailyMinutes = $this->calculateMinutesFromEvents($sortedEvents);

            // Determine shift details (first in - last out)
            $firstIn = $sortedEvents->first(fn($e) => $e->isCheckIn());
            $lastOut = $sortedEvents->last(fn($e) => $e->isCheckOut());

            $isRemote = false;
            $locationName = 'Office';

            if ($firstIn) {
                if ($firstIn->isDelegationStart()) {
                    $isRemote = true;
                    $locationName = 'Delegation';
                } elseif ($firstIn->workplace) {
                    $locationName = $firstIn->workplace->name;
                    if (stripos($firstIn->workplace->name, 'Home') !== false || stripos($firstIn->workplace->name, 'Remote') !== false) {
                        $isRemote = true;
                    }
                } elseif (!$firstIn->workplace_id) {
                    $isRemote = true;
                    $locationName = 'Remote';
                }
            }

            $activityLog[] = [
                'date' => Carbon::parse($dateStr),
                'minutes' => $dailyMinutes,
                'hours_str' => floor($dailyMinutes / 60) . 'h ' . ($dailyMinutes % 60) . 'm',
                'start_time' => $firstIn ? $firstIn->event_time->format('H:i') : '-',
                'end_time' => $lastOut ? $lastOut->event_time->format('H:i') : (Carbon::parse($dateStr)->isToday() ? 'Now' : '-'),
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

        $this->data['next_leave'] = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'APPROVED')
            ->where('start_date', '>=', Carbon::today())
            ->orderBy('start_date')
            ->first();

        // --------------------------
        // 4. Alerts
        // --------------------------
        $this->data['alerts'] = $this->alertService->getAlerts($user);

        return view('admin.dashboard.employee', $this->data);
    }

    private function calculateMinutesFromEvents($events)
    {
        $totalMinutes = 0;
        $currentCheckIn = null;

        foreach ($events as $event) {
            if ($event->isCheckIn()) {
                $currentCheckIn = $event;
            } elseif ($event->isCheckOut() && $currentCheckIn !== null) {
                $totalMinutes += (int) $currentCheckIn->event_time->diffInMinutes($event->event_time);
                $currentCheckIn = null;
            }
        }

        return $totalMinutes;
    }
}
