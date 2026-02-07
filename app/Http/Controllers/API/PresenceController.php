<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\CheckInRequest;
use App\Http\Requests\API\CheckOutRequest;
use App\Http\Resources\API\PresenceEventResource;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PresenceController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService
    ) {}

    /**
     * Handle user check-in.
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;
        if (!$employee) return response()->json(['message' => 'Employee profile not found'], 404);

        $event = $this->presenceService->checkIn(
            $employee,
            $request->validated()
        );

        return response()->json([
            'message' => 'Checked in successfully.',
            'event' => new PresenceEventResource($event->load('workplace')),
        ], 201);
    }

    /**
     * Handle user check-out.
     */
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;
        if (!$employee) return response()->json(['message' => 'Employee profile not found'], 404);

        $event = $this->presenceService->checkOut(
            $employee,
            $request->validated()
        );

        return response()->json([
            'message' => 'Checked out successfully.',
            'event' => new PresenceEventResource($event->load('workplace')),
        ], 201);
    }

    /**
     * Get current presence status.
     */
    public function current(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
             return response()->json([
                'is_present' => false,
                'latest_event' => null,
                'current_workplace' => null,
                'duration_minutes' => null,
            ], 200);
        }

        $latestEvent = $employee->latestPresenceEvent;

        if (! $latestEvent) {
            return response()->json([
                'is_present' => false,
                'latest_event' => null,
                'current_workplace' => null,
                'duration_minutes' => null,
            ], 200);
        }

        $isPresent = $latestEvent->event_type === 'check_in';
        $currentWorkplace = $isPresent ? $employee->getCurrentWorkplace() : null;
        $durationMinutes = null;

        if ($isPresent && $latestEvent->event_time) {
            $durationMinutes = (int) $latestEvent->event_time->diffInMinutes(now());
        }

        return response()->json([
            'is_present' => $isPresent,
            'latest_event' => new PresenceEventResource($latestEvent->load('workplace')),
            'current_workplace' => $currentWorkplace ? $currentWorkplace->name : null,
            'duration_minutes' => $durationMinutes,
        ], 200);
    }

    /**
     * Get paginated presence history.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $employee = $request->user()->employee;
        if (!$employee) {
             return PresenceEventResource::collection([]);
        }

        $events = $employee
            ->presenceEvents()
            ->with('workplace')
            ->orderBy('event_time', 'desc')
            ->paginate(20);

        return PresenceEventResource::collection($events);
    }

    /**
     * Get today's presence events with total minutes.
     */
    public function today(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
             return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $events = $employee->presenceEvents()
            ->with('workplace')
            ->whereDate('event_time', today())
            ->orderBy('event_time', 'asc')
            ->get();

        $totalMinutes = $employee->getTodayMinutes();

        $sessions = [];
        $currentCheckIn = null;

        foreach ($events as $event) {
            if ($event->event_type === 'check_in') {
                $currentCheckIn = $event;
            } elseif ($event->event_type === 'check_out' && $currentCheckIn) {
                $sessions[] = [
                    'check_in' => new PresenceEventResource($currentCheckIn),
                    'check_out' => new PresenceEventResource($event),
                    'duration_minutes' => $currentCheckIn->event_time->diffInMinutes($event->event_time),
                ];
                $currentCheckIn = null;
            }
        }

        // If there's an ongoing session (checked in but not checked out)
        if ($currentCheckIn) {
            $sessions[] = [
                'check_in' => new PresenceEventResource($currentCheckIn),
                'check_out' => null,
                'duration_minutes' => $currentCheckIn->event_time->diffInMinutes(now()),
            ];
        }

        // Calculate weekly stats
        // Target: 8 hours (480 mins) per day * number of days passed in week (inclusive)
        $dayOfWeek = now()->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        $targetMinutes = $dayOfWeek * 480;
        $weeklyMinutes = $employee->getWeekMinutes();

        $onTrack = 'on_track';
        if ($weeklyMinutes > $targetMinutes + 60) {
            $onTrack = 'over_time';
        } elseif ($weeklyMinutes < $targetMinutes - 60) {
            $onTrack = 'behind_schedule';
        }

        return response()->json([
            'date' => today()->toDateString(),
            'total_minutes' => $totalMinutes,
            'sessions' => $sessions,
            'this_week' => [
                'total_minutes' => $weeklyMinutes,
                'on_track' => $onTrack,
            ],
        ], 200);
    }
}
