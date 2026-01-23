<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\PresenceEvent;
use App\Models\User;
use App\Models\Workplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresenceService
{
    /**
     * Process a user check-in.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function checkIn(User $user, array $data): PresenceEvent
    {
        // Check if user is on approved leave today
        $onLeave = LeaveRequest::where('user_id', $user->id)
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

        // Check if user is already checked in
        // Allow delegation_start to proceed even if checked in (it starts a nested/parallel event or switches context)
        $latestEvent = $user->latestPresenceEvent;
        if ($eventType !== 'delegation_start' && $latestEvent && ($latestEvent->isCheckIn())) {
            throw ValidationException::withMessages([
                'status' => ['You are already checked in. Please check out first.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data, $eventType, $workplaceId) {
            return PresenceEvent::create([
                'user_id' => $user->id,
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
     * Process a user check-out.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function checkOut(User $user, array $data): PresenceEvent
    {
        // Get the latest unpaired check-in event
        $latestCheckIn = $user->presenceEvents()
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

        return DB::transaction(function () use ($user, $latestCheckIn, $data, $eventType) {
            $checkOut = PresenceEvent::create([
                'user_id' => $user->id,
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
    public function delegationEndOnly(User $user, array $data): PresenceEvent
    {
        $latestCheckIn = $user->presenceEvents()
            ->where('event_type', 'delegation_start')
            ->whereNull('pair_event_id')
            ->latest('event_time')
            ->first();

        if (! $latestCheckIn) {
            throw ValidationException::withMessages([
                'status' => ['You are not in a delegation.'],
            ]);
        }

        return DB::transaction(function () use ($user, $latestCheckIn, $data) {
            $delegationEnd = PresenceEvent::create([
                'user_id' => $user->id,
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
}
