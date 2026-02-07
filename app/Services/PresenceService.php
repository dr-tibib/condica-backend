<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PresenceEvent;
use App\Models\Workplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresenceService
{
    /**
     * Process an employee check-in.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function checkIn(Employee $employee, array $data): PresenceEvent
    {
        // Check if employee is on approved leave today
        $onLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        if ($onLeave) {
            throw ValidationException::withMessages([
                'status' => ['You cannot check in while on approved leave.'],
            ]);
        }

        $eventType = $data['event_type'] ?? 'check_in';
        $workplaceId = $data['workplace_id'] ?? null;

        if ($eventType === 'check_in') {
            if (! $workplaceId) {
                throw ValidationException::withMessages(['workplace_id' => 'Workplace is required for check-in.']);
            }
            $workplace = Workplace::findOrFail($workplaceId);

            // Validate geofence if location data is provided
            if (isset($data['latitude']) && isset($data['longitude'])) {
                if (! $workplace->isLocationWithinGeofence((float) $data['latitude'], (float) $data['longitude'])) {
                    throw ValidationException::withMessages([
                        'location' => ['You are not within the geofence radius of this workplace.'],
                    ]);
                }
            }
        }

        // Check if employee is already checked in
        // Allow delegation_start to proceed even if checked in (it starts a nested/parallel event or switches context)
        $latestEvent = $employee->latestPresenceEvent;
        if ($eventType !== 'delegation_start' && $latestEvent && ($latestEvent->isCheckIn())) {
            throw ValidationException::withMessages([
                'status' => ['You are already checked in. Please check out first.'],
            ]);
        }

        return DB::transaction(function () use ($employee, $data, $eventType, $workplaceId) {
            return PresenceEvent::create([
                'employee_id' => $employee->id,
                'workplace_id' => $workplaceId,
                'event_type' => $eventType,
                'event_time' => now(),
                'method' => $data['method'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'device_info' => $data['device_info'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Process an employee check-out.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function checkOut(Employee $employee, array $data): PresenceEvent
    {
        // Get the latest unpaired check-in event
        $latestCheckIn = $employee->presenceEvents()
            ->whereIn('event_type', ['check_in', 'delegation_start'])
            ->whereNull('pair_event_id')
            ->latest('event_time')
            ->first();

        if (! $latestCheckIn) {
            throw ValidationException::withMessages([
                'status' => ['You must check in before checking out.'],
            ]);
        }

        // Determine event type based on the start event
        $eventType = $latestCheckIn->event_type === 'delegation_start' ? 'delegation_end' : 'check_out';

        // Validate geofence if enabled for the workplace and it's a regular check-in
        if ($latestCheckIn->event_type === 'check_in') {
            $workplace = $latestCheckIn->workplace;
            if ($workplace && isset($data['latitude']) && isset($data['longitude'])) {
                if (! $workplace->isLocationWithinGeofence((float) $data['latitude'], (float) $data['longitude'])) {
                    throw ValidationException::withMessages([
                        'location' => ['You are not within the geofence radius of your check-in workplace.'],
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($employee, $latestCheckIn, $data, $eventType) {
            $checkOut = PresenceEvent::create([
                'employee_id' => $employee->id,
                'workplace_id' => $latestCheckIn->workplace_id,
                'event_type' => $eventType,
                'event_time' => now(),
                'method' => $data['method'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'device_info' => $data['device_info'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'notes' => $data['notes'] ?? null,
                'pair_event_id' => $latestCheckIn->id,
            ]);

            // Update the check-in with the pair event ID
            $latestCheckIn->update([
                'pair_event_id' => $checkOut->id,
            ]);

            return $checkOut;
        });
    }

    /**
     * End a delegation without checking out completely.
     */
    public function delegationEndOnly(Employee $employee, array $data): PresenceEvent
    {
        $latestCheckIn = $employee->presenceEvents()
            ->where('event_type', 'delegation_start')
            ->whereNull('pair_event_id')
            ->latest('event_time')
            ->first();

        if (! $latestCheckIn) {
            throw ValidationException::withMessages([
                'status' => ['You are not in a delegation.'],
            ]);
        }

        return DB::transaction(function () use ($employee, $latestCheckIn, $data) {
            $delegationEnd = PresenceEvent::create([
                'employee_id' => $employee->id,
                'workplace_id' => $latestCheckIn->workplace_id,
                'event_type' => 'delegation_end',
                'event_time' => now(),
                'method' => $data['method'] ?? 'manual',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'device_info' => $data['device_info'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'notes' => $data['notes'] ?? null,
                'pair_event_id' => $latestCheckIn->id,
            ]);

            // Update the delegation start with the pair event ID
            $latestCheckIn->update([
                'pair_event_id' => $delegationEnd->id,
            ]);

            return $delegationEnd;
        });
    }

    /**
     * End a delegation with a schedule (multi-day).
     *
     * @param  array<int, array{date: string, start_time: string, end_time: string}>  $schedule
     */
    public function endDelegationWithSchedule(Employee $employee, array $schedule): void
    {
        $latestDelegationStart = $employee->presenceEvents()
            ->where('event_type', 'delegation_start')
            ->whereNull('pair_event_id')
            ->latest('event_time')
            ->first();

        if (! $latestDelegationStart) {
            throw ValidationException::withMessages([
                'status' => ['You are not in a delegation.'],
            ]);
        }

        DB::transaction(function () use ($employee, $schedule, $latestDelegationStart) {
            foreach ($schedule as $index => $day) {
                $date = $day['date'];
                $startTime = $day['start_time']; // H:i
                $endTime = $day['end_time']; // H:i

                $startDateTime = \Carbon\Carbon::parse("$date $startTime");
                $endDateTime = \Carbon\Carbon::parse("$date $endTime");

                // Determine if first or last day
                $isFirstDay = ($index === 0);
                $isLastDay = ($index === count($schedule) - 1);

                if ($isFirstDay) {
                    // Fetch existing check-in BEFORE updating start time
                    $originalStartTime = $latestDelegationStart->event_time;

                    // Update existing start to match the schedule
                    $latestDelegationStart->update(['event_time' => $startDateTime]);

                    // Close First Day: DelegationEnd + CheckOut at endDateTime.
                    $delegationEnd = PresenceEvent::create([
                        'employee_id' => $employee->id,
                        'workplace_id' => $latestDelegationStart->workplace_id,
                        'event_type' => 'delegation_end',
                        'event_time' => $endDateTime,
                        'method' => 'kiosk_schedule',
                        'pair_event_id' => $latestDelegationStart->id,
                    ]);
                    $latestDelegationStart->update(['pair_event_id' => $delegationEnd->id]);

                    // Close the open CheckIn corresponding to this session
                    $checkIn = PresenceEvent::where('employee_id', $employee->id)
                        ->where('event_type', 'check_in')
                        ->whereNull('pair_event_id')
                        ->where('event_time', '<=', $originalStartTime) // Use original time to find the check-in
                        ->latest('event_time')
                        ->first();

                    if ($checkIn) {
                        // If check-in time is later than new start time, update it
                        if ($checkIn->event_time->gt($startDateTime)) {
                            $checkIn->update(['event_time' => $startDateTime]);
                        }

                        $checkOut = PresenceEvent::create([
                            'employee_id' => $employee->id,
                            'workplace_id' => $checkIn->workplace_id,
                            'event_type' => 'check_out',
                            'event_time' => $endDateTime,
                            'method' => 'kiosk_schedule',
                            'pair_event_id' => $checkIn->id,
                        ]);
                        $checkIn->update(['pair_event_id' => $checkOut->id]);
                    }

                } else {
                    // Middle or Last Day

                    // 1. CheckIn + DelegationStart
                    $checkIn = PresenceEvent::create([
                        'employee_id' => $employee->id,
                        'workplace_id' => $latestDelegationStart->workplace_id,
                        'event_type' => 'check_in',
                        'event_time' => $startDateTime,
                        'method' => 'kiosk_schedule',
                    ]);

                    $delegationStart = PresenceEvent::create([
                        'employee_id' => $employee->id,
                        'workplace_id' => $latestDelegationStart->workplace_id,
                        'event_type' => 'delegation_start',
                        'event_time' => $startDateTime,
                        'method' => 'kiosk_schedule',
                    ]);

                    if ($isLastDay) {
                        // Last Day: DelegationEnd Only (Employee stays checked in)
                        $delegationEnd = PresenceEvent::create([
                            'employee_id' => $employee->id,
                            'workplace_id' => $latestDelegationStart->workplace_id,
                            'event_type' => 'delegation_end',
                            'event_time' => $endDateTime,
                            'method' => 'kiosk_schedule',
                            'pair_event_id' => $delegationStart->id,
                        ]);
                        $delegationStart->update(['pair_event_id' => $delegationEnd->id]);

                        // CheckIn remains unpaired (Active)
                    } else {
                        // Middle Day: DelegationEnd + CheckOut
                        $delegationEnd = PresenceEvent::create([
                            'employee_id' => $employee->id,
                            'workplace_id' => $latestDelegationStart->workplace_id,
                            'event_type' => 'delegation_end',
                            'event_time' => $endDateTime,
                            'method' => 'kiosk_schedule',
                            'pair_event_id' => $delegationStart->id,
                        ]);
                        $delegationStart->update(['pair_event_id' => $delegationEnd->id]);

                        $checkOut = PresenceEvent::create([
                            'employee_id' => $employee->id,
                            'workplace_id' => $latestDelegationStart->workplace_id,
                            'event_type' => 'check_out',
                            'event_time' => $endDateTime,
                            'method' => 'kiosk_schedule',
                            'pair_event_id' => $checkIn->id,
                        ]);
                        $checkIn->update(['pair_event_id' => $checkOut->id]);
                    }
                }
            }
        });
    }
}
