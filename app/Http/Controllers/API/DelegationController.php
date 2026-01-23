<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\User;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DelegationController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService
    ) {}

    /**
     * Get recent unique delegation locations for the user (or all distinct).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        $query = Delegation::select('place_id', 'name', 'address', 'latitude', 'longitude')
            ->whereNotNull('place_id');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $delegations = $query->latest('created_at')
            ->limit(50)
            ->get()
            ->unique('place_id')
            ->take(10)
            ->values();

        return response()->json([
            'data' => $delegations,
        ]);
    }

    /**
     * Start a delegation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'place_id' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'device_info' => ['nullable', 'array'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        try {
            return DB::transaction(function () use ($user, $validated) {
                // 1. Create Presence Event
                $event = $this->presenceService->checkIn($user, [
                    'event_type' => 'delegation_start',
                    'method' => 'kiosk',
                    'device_info' => $validated['device_info'] ?? null,
                    'notes' => 'Delegation at ' . $validated['name'],
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ]);

                // 2. Create Delegation Record
                $delegation = Delegation::create([
                    'user_id' => $user->id,
                    'place_id' => $validated['place_id'] ?? null,
                    'name' => $validated['name'],
                    'address' => $validated['address'] ?? null,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'start_event_id' => $event->id,
                ]);

                return response()->json([
                    'message' => 'Delegation started successfully.',
                    'type' => 'delegation-start',
                    'user' => ['name' => $user->name],
                    'time' => $event->event_time->format('g:i A'),
                    'event' => $event,
                    'delegation' => $delegation,
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Delegation start error: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
