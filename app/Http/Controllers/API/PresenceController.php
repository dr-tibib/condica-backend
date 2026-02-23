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

        $activeEvent = $employee->presenceEvents()->active()->latest('start_at')->first();

        if (! $activeEvent) {
            return response()->json([
                'is_present' => false,
                'latest_event' => null,
                'current_workplace' => null,
                'duration_minutes' => null,
            ], 200);
        }

        $isPresent = ($activeEvent->type === 'presence');
        $currentWorkplace = $activeEvent->workplace;
        $durationMinutes = (int) $activeEvent->start_at->diffInMinutes(now());

        return response()->json([
            'is_present' => $isPresent,
            'latest_event' => new PresenceEventResource($activeEvent->load('workplace')),
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
            ->orderBy('start_at', 'desc')
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
            ->whereDate('start_at', today())
            ->orderBy('start_at', 'asc')
            ->get();

        $totalMinutes = $employee->getTodayMinutes();

        $sessions = [];

        foreach ($events as $event) {
            $sessions[] = [
                'event' => new PresenceEventResource($event),
                'type' => $event->type,
                'start_at' => $event->start_at->toDateTimeString(),
                'end_at' => $event->end_at ? $event->end_at->toDateTimeString() : null,
                'duration_minutes' => $event->end_at ? $event->start_at->diffInMinutes($event->end_at) : $event->start_at->diffInMinutes(now()),
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
