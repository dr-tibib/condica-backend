<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Employee;
use App\Models\PresenceEvent;
use Illuminate\Support\Collection;

class PresenceIssueService
{
    /**
     * Get all detected presence issues.
     *
     * @return array{
     *     unclosed_checkins: Collection<int, PresenceEvent>,
     *     leave_while_worked: Collection<int, Employee>,
     *     long_sessions: Collection<int, PresenceEvent>,
     *     total_issues: int,
     * }
     */
    public function getIssues(): array
    {
        $unclosedCheckins = $this->detectUnclosedCheckins();
        $leaveWhileWorked = $this->detectLeaveWhileWorked();
        $longSessions = $this->detectLongSessions();

        return [
            'unclosed_checkins' => $unclosedCheckins,
            'leave_while_worked' => $leaveWhileWorked,
            'long_sessions' => $longSessions,
            'total_issues' => $unclosedCheckins->count() + $leaveWhileWorked->count() + $longSessions->count(),
        ];
    }

    /**
     * Get a compact summary of unclosed check-ins for AI tool use.
     *
     * @return array{count: int, employees: array<int, array{name: string, date: string, workplace: string}>}
     */
    public function getUnclosedSummary(): array
    {
        $unclosed = $this->detectUnclosedCheckins();

        return [
            'count' => $unclosed->count(),
            'employees' => $unclosed->map(fn (PresenceEvent $event) => [
                'name' => $event->employee?->name ?? 'Unknown',
                'date' => $event->start_at->format('d.m.Y'),
                'workplace' => $event->workplace?->name ?? 'N/A',
            ])->values()->toArray(),
        ];
    }

    /**
     * Get a compact summary of employees on leave who also worked.
     *
     * @return array{count: int, employees: array<int, array{name: string, leave_type: string}>}
     */
    public function getLeaveWhileWorkedSummary(): array
    {
        $conflicts = $this->detectLeaveWhileWorked();

        return [
            'count' => $conflicts->count(),
            'employees' => $conflicts->map(function (Employee $employee) {
                $leaveType = $employee->leaveRequests->first()?->leaveType?->name ?? 'Concediu';

                return [
                    'name' => $employee->name,
                    'leave_type' => $leaveType,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get a compact summary of suspiciously long sessions for AI tool use.
     *
     * @return array{count: int, sessions: array<int, array{employee: string, duration_h: float, date: string}>}
     */
    public function getLongSessionsSummary(): array
    {
        $longSessions = $this->detectLongSessions();

        return [
            'count' => $longSessions->count(),
            'sessions' => $longSessions->map(fn (PresenceEvent $event) => [
                'employee' => $event->employee?->name ?? 'Unknown',
                'duration_h' => round($event->start_at->diffInMinutes($event->end_at) / 60, 1),
                'date' => $event->start_at->format('d.m.Y'),
            ])->values()->toArray(),
        ];
    }

    /**
     * @return Collection<int, PresenceEvent>
     */
    private function detectUnclosedCheckins(): Collection
    {
        return PresenceEvent::active()
            ->ofType('presence')
            ->where('start_at', '<', today())
            ->with(['employee', 'workplace'])
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    private function detectLeaveWhileWorked(): Collection
    {
        return Employee::whereHas('leaveRequests', function ($query) {
            $query->where('status', 'APPROVED')
                ->where('start_date', '<=', today())
                ->where('end_date', '>=', today());
        })->whereHas('presenceEvents', function ($query) {
            $query->today()->ofType('presence');
        })->with([
            'leaveRequests' => function ($query) {
                $query->where('status', 'APPROVED')
                    ->where('start_date', '<=', today())
                    ->where('end_date', '>=', today())
                    ->with('leaveType');
            },
            'presenceEvents' => function ($query) {
                $query->today()->ofType('presence');
            },
        ])->get();
    }

    /**
     * @return Collection<int, PresenceEvent>
     */
    private function detectLongSessions(): Collection
    {
        return PresenceEvent::ofType('presence')
            ->whereNotNull('end_at')
            ->where('start_at', '>=', now()->startOfMonth())
            ->with('employee')
            ->get()
            ->filter(fn (PresenceEvent $event) => $event->start_at->diffInMinutes($event->end_at) > 14 * 60)
            ->values();
    }
}
