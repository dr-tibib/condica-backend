<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\Workplace;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresenceService
{
    /**
     * Entry point for all Kiosk interactions.
     */
    public function processKioskFlow(Employee $employee, string $flow, array $data): array
    {
        $activePresence = $employee->presenceEvents()->ofType('presence')->active()->latest('start_at')->first();
        $activeDelegation = $employee->presenceEvents()->ofType('delegation')->active()->latest('start_at')->first();
        $activeLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        return DB::transaction(function () use ($employee, $flow, $data, $activePresence, $activeDelegation, $activeLeave) {
            
            if ($flow === 'regular') {
                return $this->handleNormalFlow($employee, $data, $activePresence, $activeDelegation, $activeLeave);
            }

            if ($flow === 'delegation') {
                return $this->handleDelegationFlow($employee, $data, $activePresence, $activeDelegation, $activeLeave);
            }

            if ($flow === 'leave') {
                return $this->handleLeaveFlow($employee, $data, $activePresence, $activeDelegation, $activeLeave);
            }

            throw new \Exception("Invalid flow type: {$flow}");
        });
    }

    public function startDelegation(Employee $employee, array $data): array
    {
        return DB::transaction(function () use ($employee, $data) {
            $activePresence = $employee->presenceEvents()->ofType('presence')->active()->latest('start_at')->first();
            $activeLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'APPROVED')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            // 1. End active shift if exists
            if ($activePresence) {
                $activePresence->update([
                    'end_at' => now(),
                    'end_method' => 'kiosk_delegation_start',
                ]);
            }

            // 2. Interrupt leave if exists
            if ($activeLeave) {
                $this->interruptLeave($activeLeave);
            }

            // 3. Create Delegation Presence Event
            $event = PresenceEvent::create([
                'employee_id' => $employee->id,
                'workplace_id' => $data['workplace_id'] ?? $employee->workplace_id,
                'type' => 'delegation',
                'start_at' => now(),
                'start_method' => 'kiosk',
                'start_latitude' => $data['latitude'] ?? null,
                'start_longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return [
                'event' => $event,
                'time' => $event->start_at->format('g:i A')
            ];
        });
    }

    private function handleNormalFlow(Employee $employee, array $data, ?PresenceEvent $activePresence, ?PresenceEvent $activeDelegation, ?LeaveRequest $activeLeave): array
    {
        if ($activeDelegation) {
            if (!$activeDelegation->start_at->isSameDay(now())) {
                return [
                    'type' => 'delegation_refinement_required',
                    'employee' => $employee,
                    'active_delegation' => $activeDelegation,
                    'next_step' => 'regular',
                    'timeline' => $this->generateDelegationTimeline($activeDelegation, $employee)
                ];
            }
            return $this->endDelegationAndStartShift($employee, $data, $activeDelegation);
        }

        if ($activeLeave) {
            $this->interruptLeave($activeLeave);
        }

        if ($activePresence) {
            if (!$activePresence->start_at->isSameDay(now())) {
                return [
                    'type' => 'correction_required',
                    'employee' => $employee,
                    'last_start' => $activePresence->start_at,
                    'timeline' => $this->generateCorrectionTimeline($activePresence->start_at, now()->subDay())
                ];
            }

            $activePresence->update([
                'end_at' => now(),
                'end_method' => 'kiosk',
            ]);

            return [
                'type' => 'checkout',
                'message' => 'Checked out successfully.',
                'employee' => $employee,
                'time' => now()->format('g:i A')
            ];
        }

        $workplaceId = $data['workplace_id'] ?? $employee->workplace_id;
        $workplace = Workplace::find($workplaceId);
        $threshold = $workplace ? $workplace->late_start_threshold : '16:00';
        
        if (now()->format('H:i') >= $threshold) {
            return [
                'type' => 'late_start_confirm',
                'employee' => $employee,
                'threshold' => $threshold,
                'workplace_id' => $workplaceId
            ];
        }

        $event = PresenceEvent::create([
            'employee_id' => $employee->id,
            'workplace_id' => $workplaceId,
            'type' => 'presence',
            'start_at' => now(),
            'start_method' => 'kiosk',
        ]);

        return [
            'type' => 'checkin',
            'message' => 'Checked in successfully.',
            'employee' => $employee,
            'time' => $event->start_at->format('g:i A')
        ];
    }

    private function handleDelegationFlow(Employee $employee, array $data, ?PresenceEvent $activePresence, ?PresenceEvent $activeDelegation, ?LeaveRequest $activeLeave): array
    {
        if ($activeDelegation) {
            if ($activeDelegation->start_at->diffInMinutes(now()) < 10) {
                return [
                    'type' => 'delegation_cancel_confirm',
                    'employee' => $employee,
                    'active_delegation' => $activeDelegation
                ];
            }

            if (!$activeDelegation->start_at->isSameDay(now())) {
                return [
                    'type' => 'delegation_refinement_required',
                    'employee' => $employee,
                    'active_delegation' => $activeDelegation,
                    'next_step' => 'delegation',
                    'timeline' => $this->generateDelegationTimeline($activeDelegation, $employee)
                ];
            }

            return $this->endDelegationAndStartShift($employee, $data, $activeDelegation);
        }

        return [
            'type' => 'delegation_wizard',
            'employee' => $employee,
            'active_presence' => $activePresence,
            'active_leave' => $activeLeave
        ];
    }

    private function handleLeaveFlow(Employee $employee, array $data, ?PresenceEvent $activePresence, ?PresenceEvent $activeDelegation, ?LeaveRequest $activeLeave): array
    {
        if ($activeDelegation) {
             if (!$activeDelegation->start_at->isSameDay(now())) {
                return [
                    'type' => 'delegation_refinement_required',
                    'employee' => $employee,
                    'active_delegation' => $activeDelegation,
                    'next_step' => 'leave',
                    'timeline' => $this->generateDelegationTimeline($activeDelegation, $employee)
                ];
            }
            $activeDelegation->update(['end_at' => now(), 'end_method' => 'kiosk']);
        }

        if ($activePresence) {
            $activePresence->update(['end_at' => now(), 'end_method' => 'kiosk']);
        }

        return [
            'type' => 'leave_screen',
            'employee' => $employee,
        ];
    }

    public function processLateStart(Employee $employee, array $data): array
    {
        $time = $data['time'];
        $action = $data['action'];
        $dt = Carbon::parse(now()->format('Y-m-d') . ' ' . $time);

        if ($action === 'start') {
            PresenceEvent::create([
                'employee_id' => $employee->id,
                'workplace_id' => $data['workplace_id'],
                'type' => 'presence',
                'start_at' => $dt,
                'start_method' => 'kiosk',
            ]);
            return ['type' => 'checkin', 'message' => 'Shift started retroactively.', 'employee' => $employee, 'time' => $dt->format('g:i A')];
        } else {
            PresenceEvent::create([
                'employee_id' => $employee->id,
                'workplace_id' => $data['workplace_id'],
                'type' => 'presence',
                'start_at' => $dt,
                'end_at' => $dt,
                'start_method' => 'kiosk',
                'end_method' => 'kiosk',
                'notes' => 'Retroactive end only shift'
            ]);
            return ['type' => 'checkout', 'message' => 'Shift ended retroactively.', 'employee' => $employee, 'time' => $dt->format('g:i A')];
        }
    }

    public function correctShifts(Employee $employee, array $timeline): void
    {
        DB::transaction(function() use ($employee, $timeline) {
            // 1. Close the open long-running shift first
            $active = $employee->presenceEvents()->ofType('presence')->active()->first();
            if ($active) {
                $firstDay = collect($timeline)->first();
                $this->validateTimeSequence($firstDay['start'], $firstDay['end']);
                $end = Carbon::parse($firstDay['date'] . ' ' . $firstDay['end']);
                $active->update(['end_at' => $end, 'end_method' => 'kiosk_correction']);
            }

            // 2. Create historical shifts for other days
            foreach (collect($timeline)->slice(1) as $day) {
                $this->validateTimeSequence($day['start'], $day['end']);
                PresenceEvent::create([
                    'employee_id' => $employee->id,
                    'workplace_id' => $employee->workplace_id,
                    'type' => 'presence',
                    'start_at' => Carbon::parse($day['date'] . ' ' . $day['start']),
                    'end_at' => Carbon::parse($day['date'] . ' ' . $day['end']),
                    'start_method' => 'kiosk_correction',
                    'end_method' => 'kiosk_correction',
                ]);
            }
        });
    }

    public function endDelegationWithSchedule(Employee $employee, array $schedule): void
    {
        $activeDelegation = $employee->presenceEvents()
            ->ofType('delegation')
            ->active()
            ->latest('start_at')
            ->first();

        if (! $activeDelegation) {
            throw new \Exception("You are not in a delegation.");
        }

        DB::transaction(function () use ($employee, $schedule, $activeDelegation) {
            foreach ($schedule as $index => $day) {
                $this->validateTimeSequence($day['start_time'], $day['end_time']);
                
                $date = $day['date'];
                $startTime = $day['start_time'];
                $endTime = $day['end_time'];

                $startDateTime = Carbon::parse("$date $startTime");
                $endDateTime = Carbon::parse("$date $endTime");

                $isFirstDay = ($index === 0);
                $isLastDay = ($index === count($schedule) - 1);

                if ($isFirstDay) {
                    $activeDelegation->update([
                        'start_at' => $startDateTime,
                        'end_at' => $endDateTime,
                        'end_method' => 'kiosk_schedule',
                    ]);

                    $activePresence = $employee->presenceEvents()
                        ->ofType('presence')
                        ->active()
                        ->where('start_at', '<=', $activeDelegation->start_at)
                        ->latest('start_at')
                        ->first();

                    if ($activePresence) {
                        if ($activePresence->start_at->gt($startDateTime)) {
                            $activePresence->update(['start_at' => $startDateTime]);
                        }
                        $activePresence->update(['end_at' => $endDateTime, 'end_method' => 'kiosk_schedule']);
                    }
                } else {
                    $presence = PresenceEvent::create([
                        'employee_id' => $employee->id,
                        'workplace_id' => $activeDelegation->workplace_id,
                        'type' => 'presence',
                        'start_at' => $startDateTime,
                        'start_method' => 'kiosk_schedule',
                    ]);

                    $delegation = PresenceEvent::create([
                        'employee_id' => $employee->id,
                        'workplace_id' => $activeDelegation->workplace_id,
                        'type' => 'delegation',
                        'start_at' => $startDateTime,
                        'start_method' => 'kiosk_schedule',
                        'linkable_id' => $activeDelegation->linkable_id,
                        'linkable_type' => $activeDelegation->linkable_type,
                    ]);

                    if ($isLastDay) {
                        $delegation->update(['end_at' => $endDateTime, 'end_method' => 'kiosk_schedule']);
                    } else {
                        $delegation->update(['end_at' => $endDateTime, 'end_method' => 'kiosk_schedule']);
                        $presence->update(['end_at' => $endDateTime, 'end_method' => 'kiosk_schedule']);
                    }
                }
            }
        });
    }

    private function validateTimeSequence(string $start, string $end): void
    {
        $startDt = Carbon::parse($start);
        $endDt = Carbon::parse($end);
        if ($startDt->gte($endDt)) {
            throw new \Exception("Invalid time sequence: start time ({$start}) must be before end time ({$end}).");
        }
    }

    public function cancelDelegation(Employee $employee, int $eventId): void
    {
        DB::transaction(function() use ($employee, $eventId) {
            $delegationEvent = PresenceEvent::findOrFail($eventId);
            if ($delegationEvent->employee_id !== $employee->id) throw new \Exception("Unauthorized");

            // 1. Delete delegation and its linkable
            if ($delegationEvent->linkable) $delegationEvent->linkable->delete();
            $delegationEvent->delete();

            // 2. Resume previous shift if it was ended today
            $previousShift = $employee->presenceEvents()
                ->ofType('presence')
                ->whereNotNull('end_at')
                ->whereDate('start_at', now())
                ->latest('end_at')
                ->first();

            if ($previousShift) {
                $previousShift->update(['end_at' => null]);
            }
        });
    }

    public function startLeave(Employee $employee, array $data): LeaveRequest
    {
        $type = LeaveType::first(); // Default
        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_days' => $data['total_days'],
            'status' => 'APPROVED',
        ]);
    }

    private function endDelegationAndStartShift(Employee $employee, array $data, PresenceEvent $activeDelegation): array
    {
        $activeDelegation->update(['end_at' => now(), 'end_method' => 'kiosk']);
        $event = PresenceEvent::create([
            'employee_id' => $employee->id,
            'workplace_id' => $data['workplace_id'] ?? $employee->workplace_id,
            'type' => 'presence',
            'start_at' => now(),
            'start_method' => 'kiosk',
        ]);
        return ['type' => 'checkin', 'message' => 'Delegation ended, Shift started.', 'employee' => $employee, 'time' => now()->format('g:i A')];
    }

    private function interruptLeave(LeaveRequest $leave): void
    {
        $yesterday = now()->subDay()->endOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $originalEnd = $leave->end_date;

        if ($leave->start_date->gt($yesterday)) {
            $leave->delete();
        } else {
            $leave->update(['end_date' => $yesterday, 'total_days' => $this->calculateBusinessDays($leave->start_date, $yesterday)]);
        }

        if ($originalEnd->gte($tomorrow)) {
            LeaveRequest::create([
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'start_date' => $tomorrow,
                'end_date' => $originalEnd,
                'total_days' => $this->calculateBusinessDays($tomorrow, $originalEnd),
                'status' => 'APPROVED',
            ]);
        }
    }

    private function generateCorrectionTimeline(Carbon $start, Carbon $end): array
    {
        $timeline = [];
        $period = CarbonPeriod::create($start->startOfDay(), '1 day', $end->endOfDay());
        foreach ($period as $date) {
            if ($date->isWeekend()) continue;
            $timeline[] = ['date' => $date->format('Y-m-d'), 'start' => '08:00', 'end' => '17:00'];
        }
        return $timeline;
    }

    private function generateDelegationTimeline(PresenceEvent $delegation, Employee $employee): array
    {
        $timeline = [];
        $period = CarbonPeriod::create($delegation->start_at->startOfDay(), '1 day', now()->endOfDay());
        foreach ($period as $date) {
            if ($date->isWeekend()) continue;
            $start = ($date->isSameDay($delegation->start_at)) ? $delegation->start_at->format('H:i') : '08:00';
            $end = ($date->isToday()) ? now()->format('H:i') : '17:00';
            $timeline[] = ['date' => $date->format('Y-m-d'), 'start' => $start, 'end' => $end];
        }
        return $timeline;
    }

    private function calculateBusinessDays(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $period = CarbonPeriod::create($start, '1 day', $end);
        foreach ($period as $date) {
            if (!$date->isWeekend()) $count++;
        }
        return $count;
    }
}
