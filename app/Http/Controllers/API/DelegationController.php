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

        $query = \App\Models\DelegationStop::select(
                'delegation_stops.place_id', 
                'delegation_stops.name', 
                'delegation_stops.address', 
                'delegation_stops.latitude', 
                'delegation_stops.longitude',
                'delegation_places.photo_reference'
            )
            ->leftJoin('delegation_places', 'delegation_stops.delegation_place_id', '=', 'delegation_places.id')
            ->whereNotNull('delegation_stops.place_id');

        if ($employeeId) {
            $query->whereHas('delegation', function($q) use ($employeeId) {
                $q->where('employee_id', (int) $employeeId);
            });
        }

        $delegations = $query->latest('delegation_stops.created_at')
            ->limit(100)
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
            'places' => ['nullable', 'array'],
            'places.*.name' => ['required', 'string'],
            'places.*.place_id' => ['nullable', 'string'],
            'places.*.address' => ['nullable', 'string'],
            'places.*.latitude' => ['nullable', 'numeric'],
            'places.*.longitude' => ['nullable', 'numeric'],
            'places.*.photo_reference' => ['nullable', 'string'],
            // Backwards compatibility for single place
            'place_id' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'required_without:places'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo_reference' => ['nullable', 'string'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'device_info' => ['nullable', 'array'],
            'workplace_id' => ['nullable', 'exists:workplaces,id'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        // Normalize places to an array
        $placesData = $validated['places'] ?? [];
        if (empty($placesData) && !empty($validated['name'])) {
            $placesData[] = [
                'name' => $validated['name'],
                'place_id' => $validated['place_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'photo_reference' => $validated['photo_reference'] ?? null,
            ];
        }

        try {
            return DB::transaction(function () use ($employee, $validated, $placesData) {
                // 1. Finalize start via Service
                $mainPlaceName = !empty($placesData) ? $placesData[0]['name'] : 'External';
                $startResult = $this->presenceService->startDelegation($employee, [
                    'workplace_id' => $validated['workplace_id'] ?? $employee->workplace_id,
                    'latitude' => $validated['latitude'] ?? ($placesData[0]['latitude'] ?? null),
                    'longitude' => $validated['longitude'] ?? ($placesData[0]['longitude'] ?? null),
                    'notes' => 'Delegation at ' . $mainPlaceName . (count($placesData) > 1 ? ' and others' : ''),
                ]);

                $event = $startResult['event'];

                // 2. Create Delegation Record (header)
                $delegation = \App\Models\Delegation::create([
                    'employee_id' => $employee->id,
                    'presence_event_id' => $event->id,
                    'vehicle_id' => $validated['vehicle_id'] ?? null,
                    // Keep first place for backward compatibility in main table if needed
                    'place_id' => $placesData[0]['place_id'] ?? null,
                    'name' => $placesData[0]['name'] ?? null,
                    'address' => $placesData[0]['address'] ?? null,
                    'latitude' => $placesData[0]['latitude'] ?? null,
                    'longitude' => $placesData[0]['longitude'] ?? null,
                ]);

                // 3. Create Delegation Stops
                foreach ($placesData as $place) {
                    $delegationPlaceId = null;
                    if (!empty($place['place_id'])) {
                        try {
                            $delegationPlace = DelegationPlace::updateOrCreate(
                                ['google_place_id' => $place['place_id']],
                                [
                                    'name' => $place['name'],
                                    'address' => $place['address'] ?? null,
                                    'latitude' => $place['latitude'] ?? null,
                                    'longitude' => $place['longitude'] ?? null,
                                    'photo_reference' => $place['photo_reference'] ?? null,
                                ]
                            );
                            $delegationPlaceId = $delegationPlace->id;
                        } catch (\Exception $e) {
                            Log::warning('Failed to create DelegationPlace: ' . $e->getMessage());
                        }
                    }

                    \App\Models\DelegationStop::create([
                        'delegation_id' => $delegation->id,
                        'delegation_place_id' => $delegationPlaceId,
                        'place_id' => $place['place_id'] ?? null,
                        'name' => $place['name'],
                        'address' => $place['address'] ?? null,
                        'latitude' => $place['latitude'] ?? null,
                        'longitude' => $place['longitude'] ?? null,
                    ]);
                }

                // 4. Link back event to delegation (polymorphic)
                $event->update([
                    'linkable_id' => $delegation->id,
                    'linkable_type' => \App\Models\Delegation::class,
                ]);

                return response()->json([
                    'message' => 'Delegation started successfully.',
                    'type' => 'delegation-start',
                    'employee' => $employee,
                    'time' => $startResult['time'],
                    'event' => $event,
                    'delegation' => $delegation->load('stops'),
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
