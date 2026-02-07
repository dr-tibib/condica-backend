<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DelegationPlace;
use App\Models\Employee;
use App\Models\PresenceEvent;
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
     * Get recent unique delegation locations for the employee.
     */
    public function index(Request $request): JsonResponse
    {
        $employeeId = $request->query('employee_id');

        $query = Delegation::select('place_id', 'name', 'address', 'latitude', 'longitude')
            ->whereNotNull('place_id');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
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
            'employee_id' => ['required', 'exists:employees,id'],
            'place_id' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo_reference' => ['nullable', 'string'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'device_info' => ['nullable', 'array'],
            'workplace_id' => ['nullable', 'exists:workplaces,id'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        // Find or create DelegationPlace if Google Place ID is present
        $delegationPlaceId = null;
        if (!empty($validated['place_id'])) {
            try {
                $delegationPlace = DelegationPlace::updateOrCreate(
                    ['google_place_id' => $validated['place_id']],
                    [
                        'name' => $validated['name'],
                        'address' => $validated['address'] ?? null,
                        'latitude' => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                        'photo_reference' => $validated['photo_reference'] ?? null,
                    ]
                );
                $delegationPlaceId = $delegationPlace->id;
            } catch (\Exception $e) {
                // Ignore errors creating delegation place, fallback to just storing in delegation table
                Log::warning('Failed to create DelegationPlace: ' . $e->getMessage());
            }
        }

        try {
            return DB::transaction(function () use ($employee, $validated, $delegationPlaceId) {
                // 0. Auto Check-in if not present
                if (! $employee->isCurrentlyPresent()) {
                    $workplaceId = $validated['workplace_id'] ?? $employee->workplace_id;

                    // We need a workplace to check in.
                    if ($workplaceId) {
                        PresenceEvent::create([
                            'employee_id' => $employee->id,
                            'workplace_id' => $workplaceId,
                            'event_type' => 'check_in',
                            'event_time' => now()->subSecond(),
                            'method' => 'auto',
                            'notes' => 'Auto check-in for delegation',
                        ]);
                    }
                }

                // 1. Create Presence Event
                $event = $this->presenceService->checkIn($employee, [
                    'event_type' => 'delegation_start',
                    'method' => 'kiosk',
                    'device_info' => $validated['device_info'] ?? null,
                    'notes' => 'Delegation at ' . $validated['name'],
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ]);

                // 2. Create Delegation Record
                $delegation = Delegation::create([
                    'employee_id' => $employee->id,
                    'place_id' => $validated['place_id'] ?? null,
                    'name' => $validated['name'],
                    'address' => $validated['address'] ?? null,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'start_event_id' => $event->id,
                    'vehicle_id' => $validated['vehicle_id'] ?? null,
                    'delegation_place_id' => $delegationPlaceId,
                ]);

                return response()->json([
                    'message' => 'Delegation started successfully.',
                    'type' => 'delegation-start',
                    'employee' => ['name' => $employee->name],
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
