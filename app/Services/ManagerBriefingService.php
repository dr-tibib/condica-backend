<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class ManagerBriefingService
{
    /**
     * Gather team attendance data for a given manager and date.
     *
     * @return array{
     *     manager: Employee,
     *     date: string,
     *     present: array<int, string>,
     *     absent: array<int, string>,
     *     on_leave: array<int, string>,
     *     on_delegation: array<int, string>,
     *     unclosed_yesterday: array<int, string>,
     * }
     */
    public function gatherTeamData(Employee $manager, Carbon $date): array
    {
        $subordinates = $manager->subordinates()->with([
            'presenceEvents' => fn ($q) => $q->whereDate('start_at', $date)->orWhereDate('start_at', $date->copy()->subDay()),
            'leaveRequests' => fn ($q) => $q->where('status', 'APPROVED')
                ->where('start_date', '<=', $date->toDateString())
                ->where('end_date', '>=', $date->toDateString()),
        ])->get();

        $present = [];
        $absent = [];
        $onLeave = [];
        $onDelegation = [];
        $unclosedYesterday = [];

        foreach ($subordinates as $subordinate) {
            $name = $subordinate->name;

            $isOnLeave = $subordinate->leaveRequests->isNotEmpty();

            $todayEvents = $subordinate->presenceEvents->filter(
                fn ($e) => Carbon::parse($e->start_at)->isSameDay($date)
            );

            $yesterdayUnclosed = $subordinate->presenceEvents->filter(
                fn ($e) => Carbon::parse($e->start_at)->isSameDay($date->copy()->subDay()) && $e->end_at === null
            );

            if ($yesterdayUnclosed->isNotEmpty()) {
                $unclosedYesterday[] = $name;
            }

            if ($isOnLeave) {
                $onLeave[] = $name;

                continue;
            }

            $hasDelegation = $todayEvents->where('type', 'delegation')->isNotEmpty();
            $hasPresence = $todayEvents->where('type', 'presence')->isNotEmpty();

            if ($hasDelegation) {
                $onDelegation[] = $name;
            } elseif ($hasPresence) {
                $present[] = $name;
            } else {
                $absent[] = $name;
            }
        }

        return [
            'manager' => $manager,
            'date' => $date->format('d.m.Y'),
            'present' => $present,
            'absent' => $absent,
            'on_leave' => $onLeave,
            'on_delegation' => $onDelegation,
            'unclosed_yesterday' => $unclosedYesterday,
        ];
    }

    /**
     * Return all managers (employees that have at least one subordinate).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    public function getAllManagers(): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::whereHas('subordinates')->get();
    }
}
