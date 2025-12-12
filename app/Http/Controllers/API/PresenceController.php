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
        $event = $this->presenceService->checkIn(
            $request->user(),
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
        $event = $this->presenceService->checkOut(
            $request->user(),
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
        $user = $request->user();
        $latestEvent = $user->latestPresenceEvent;

        if (! $latestEvent) {
            return response()->json([
                'is_present' => false,
                'latest_event' => null,
                'current_workplace' => null,
                'duration_minutes' => null,
            ], 200);
        }

        $isPresent = $latestEvent->event_type === 'check_in';
        $currentWorkplace = $isPresent ? $user->getCurrentWorkplace() : null;
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
        $events = $request->user()
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
        $user = $request->user();
        $events = $user->presenceEvents()
            ->with('workplace')
            ->whereDate('event_time', today())
            ->orderBy('event_time', 'asc')
            ->get();

        $totalMinutes = $user->getTodayMinutes();

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

        return response()->json([
            'date' => today()->toDateString(),
            'total_minutes' => $totalMinutes,
            'sessions' => $sessions,
        ], 200);
    }
}
