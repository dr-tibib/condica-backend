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

        $workplace = Workplace::findOrFail($data['workplace_id']);

        // Validate geofence if location data is provided
        if (isset($data['latitude']) && isset($data['longitude'])) {
            if (! $workplace->isLocationWithinGeofence((float) $data['latitude'], (float) $data['longitude'])) {
                throw ValidationException::withMessages([
                    'location' => ['You are not within the geofence radius of this workplace.'],
                ]);
            }
        }

        // Check if user is already checked in
        $latestEvent = $user->latestPresenceEvent;
        if ($latestEvent && $latestEvent->event_type === 'check_in') {
            throw ValidationException::withMessages([
                'status' => ['You are already checked in. Please check out first.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            return PresenceEvent::create([
                'user_id' => $user->id,
                'workplace_id' => $data['workplace_id'],
                'event_type' => 'check_in',
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
        // Get the latest check-in event
        $latestCheckIn = $user->latestCheckIn;

        if (! $latestCheckIn) {
            throw ValidationException::withMessages([
                'status' => ['You must check in before checking out.'],
            ]);
        }

        // Validate geofence if enabled for the workplace
        $workplace = $latestCheckIn->workplace;
        if (isset($data['latitude']) && isset($data['longitude'])) {
            if (! $workplace->isLocationWithinGeofence((float) $data['latitude'], (float) $data['longitude'])) {
                throw ValidationException::withMessages([
                    'location' => ['You are not within the geofence radius of your check-in workplace.'],
                ]);
            }
        }

        return DB::transaction(function () use ($user, $latestCheckIn, $data) {
            $checkOut = PresenceEvent::create([
                'user_id' => $user->id,
                'workplace_id' => $latestCheckIn->workplace_id,
                'event_type' => 'check_out',
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
}
