<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Services\AIService;
use App\Services\Dashboard\PresenceIssueService;
use Backpack\CRUD\app\Http\Controllers\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Prism\Prism\Tool;

class TenantAdminDashboardController extends AdminController
{
    /** @var array<int, string> */
    private static array $monthsRo = [
        'Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie',
        'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie',
    ];

    public function __construct(
        private readonly PresenceIssueService $issueService,
        private readonly AIService $aiService,
    ) {
        parent::__construct();
    }

    public function dashboard(): \Illuminate\Contracts\View\View
    {
        $this->data['title'] = trans('backpack::base.dashboard');
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin') => backpack_url('dashboard'),
            trans('backpack::base.dashboard') => false,
        ];

        $todayStats = $this->buildTodayStats();
        $issues = $this->issueService->getIssues();

        $month = (int) now()->month;
        $year = (int) now()->year;
        $monthName = self::$monthsRo[$month - 1];

        return view('admin.dashboard.tenant_admin', array_merge($this->data, [
            'todayStats' => $todayStats,
            'issues' => $issues,
            'monthName' => $monthName,
            'year' => $year,
        ]));
    }

    public function aiInsights(): JsonResponse
    {
        $month = (int) now()->month;
        $year = (int) now()->year;
        $monthName = self::$monthsRo[$month - 1];
        $companyName = (string) (tenant('company_name') ?? tenant('id') ?? 'Condica');

        $cacheKey = 'admin_insights_'.tenant('id').'_'.$year.'_'.$month;

        if (request()->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $issues = $this->issueService->getIssues();

        $html = Cache::remember($cacheKey, now()->addHours(6), function () use ($issues, $monthName, $year, $companyName): string {
            $tools = $this->buildPrismTools($issues);
            $text = $this->aiService->generateAdminInsights($tools, $monthName, (string) $year, $companyName);

            return (string) Str::markdown($text);
        });

        return response()->json([
            'html' => $html,
            'cached_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    /**
     * @return array{present: int, on_leave: int, on_delegation: int, total: int}
     */
    private function buildTodayStats(): array
    {
        return [
            'present' => Employee::whereHas('presenceEvents', function ($query) {
                $query->active()->today()->ofType('presence');
            })->count(),
            'on_leave' => Employee::whereHas('leaveRequests', function ($query) {
                $query->where('status', 'APPROVED')
                    ->where('start_date', '<=', today())
                    ->where('end_date', '>=', today());
            })->count(),
            'on_delegation' => Employee::whereHas('presenceEvents', function ($query) {
                $query->active()->today()->ofType('delegation');
            })->count(),
            'total' => Employee::count(),
        ];
    }

    /**
     * @param  array{unclosed_checkins: \Illuminate\Support\Collection, leave_while_worked: \Illuminate\Support\Collection, long_sessions: \Illuminate\Support\Collection, total_issues: int}  $issues
     * @return Tool[]
     */
    private function buildPrismTools(array $issues): array
    {
        $summary = $this->buildMonthSummary();
        $deptBreakdown = $this->buildDeptBreakdown();

        $unclosedSummary = $this->issueService->getUnclosedSummary();
        $leaveConflictSummary = $this->issueService->getLeaveWhileWorkedSummary();
        $longSessionsSummary = $this->issueService->getLongSessionsSummary();

        $issuesPayload = json_encode([
            'unclosed_checkins' => $issues['unclosed_checkins']->count(),
            'leave_while_worked' => $issues['leave_while_worked']->count(),
            'long_sessions' => $issues['long_sessions']->count(),
            'details' => [
                'unclosed' => $unclosedSummary,
                'leave_conflict' => $leaveConflictSummary,
                'long' => $longSessionsSummary,
            ],
        ]);

        return [
            (new Tool)
                ->as('get_month_summary')
                ->for('Get current month attendance statistics: total employees, hours worked, leave days, absenteeism rate')
                ->using(fn (): string => json_encode($summary)),

            (new Tool)
                ->as('get_issues_summary')
                ->for('Get presence anomalies: unclosed check-ins, employees on leave who also worked, suspiciously long sessions over 14 hours')
                ->using(fn (): string => $issuesPayload),

            (new Tool)
                ->as('get_department_breakdown')
                ->for('Get attendance statistics per department: average hours worked and absenteeism count')
                ->using(fn (): string => json_encode($deptBreakdown)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMonthSummary(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $events = PresenceEvent::ofType('presence')
            ->whereNotNull('end_at')
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->get(['employee_id', 'start_at', 'end_at']);

        $totalMinutes = $events->sum(fn ($event) => $event->start_at->diffInMinutes($event->end_at));
        $totalHours = round($totalMinutes / 60, 1);

        $employeeIdsWithPresence = $events->pluck('employee_id')->unique();
        $uniqueEmployeeCount = $employeeIdsWithPresence->count();
        $avgHours = $uniqueEmployeeCount > 0 ? round($totalHours / $uniqueEmployeeCount, 1) : 0;

        $allEmployees = Employee::get(['id', 'first_name', 'last_name']);
        $totalEmployees = $allEmployees->count();

        $employeesWithoutPresence = $allEmployees
            ->whereNotIn('id', $employeeIdsWithPresence)
            ->map(fn (Employee $e) => $e->name)
            ->values()
            ->toArray();

        return [
            'total_employees' => $totalEmployees,
            'employees_with_presence' => $uniqueEmployeeCount,
            'employees_without_presence_count' => count($employeesWithoutPresence),
            'employees_without_presence_names' => $employeesWithoutPresence,
            'total_hours_worked' => $totalHours,
            'avg_hours_per_employee' => $avgHours,
        ];
    }

    /**
     * @return array<int, array{department: string, employee_count: int, total_hours: float, avg_hours: float}>
     */
    private function buildDeptBreakdown(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $employees = Employee::with([
            'department',
            'presenceEvents' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->ofType('presence')
                    ->whereNotNull('end_at')
                    ->whereBetween('start_at', [$startOfMonth, $endOfMonth]);
            },
        ])->get();

        return $employees
            ->groupBy(fn (Employee $employee) => $employee->department?->name ?? 'Fără departament')
            ->map(function ($groupEmployees, string $deptName): array {
                $totalMinutes = 0;

                foreach ($groupEmployees as $employee) {
                    foreach ($employee->presenceEvents as $event) {
                        $totalMinutes += $event->start_at->diffInMinutes($event->end_at);
                    }
                }

                $count = $groupEmployees->count();
                $totalHours = round($totalMinutes / 60, 1);

                return [
                    'department' => $deptName,
                    'employee_count' => $count,
                    'total_hours' => $totalHours,
                    'avg_hours' => $count > 0 ? round($totalHours / $count, 1) : 0,
                ];
            })
            ->values()
            ->toArray();
    }
}
