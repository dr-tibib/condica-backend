<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DelegationPlace;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PresenceEvent;
use App\Models\Vehicle;
use App\Services\GooglePlacesService;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService,
        private readonly GooglePlacesService $googlePlacesService
    ) {}

    public function getVehicles(): JsonResponse
    {
        return response()->json([
            'data' => Vehicle::orderBy('name')->get(),
        ]);
    }

    public function getSavedPlaces(): JsonResponse
    {
        return response()->json([
            'data' => DelegationPlace::orderBy('name')->get(),
        ]);
    }

    public function searchPlaces(Request $request): JsonResponse
    {
        $query = $request->query('query');
        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        $result = $this->googlePlacesService->searchPlace($query);

        return response()->json([
            'data' => $result ? [$result] : [],
        ]);
    }

    public function getDashboardData(): JsonResponse
    {
        $events = PresenceEvent::with(['employee', 'workplace'])
            ->orderByRaw('COALESCE(end_at, start_at) DESC')
            ->take(30)
            ->get();

        $latestLogins = [];
        foreach ($events as $event) {
            $latestLogins[] = [
                'id' => $event->id.'_start',
                'employee' => $event->employee->name ?? 'Unknown',
                'avatar' => $event->employee->avatar_url ?? null,
                'time' => $event->start_at->format('H:i'),
                'date' => $event->start_at->format('d.m.Y'),
                'type' => $event->type === 'delegation' ? 'delegation_start' : 'check_in',
                'workplace' => $event->workplace->name ?? 'External',
                'timestamp' => $event->start_at->timestamp,
            ];

            if ($event->end_at) {
                $latestLogins[] = [
                    'id' => $event->id.'_end',
                    'employee' => $event->employee->name ?? 'Unknown',
                    'avatar' => $event->employee->avatar_url ?? null,
                    'time' => $event->end_at->format('H:i'),
                    'date' => $event->end_at->format('d.m.Y'),
                    'type' => $event->type === 'delegation' ? 'delegation_end' : 'check_out',
                    'workplace' => $event->workplace->name ?? 'External',
                    'timestamp' => $event->end_at->timestamp,
                ];
            }
        }

        usort($latestLogins, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        $latestLogins = array_slice($latestLogins, 0, 20);

        $onLeave = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee' => $leave->employee->name ?? 'Unknown',
                    'avatar' => $leave->employee->avatar_url ?? null,
                    'until' => $leave->end_date->format('d.m.Y'),
                    'type' => $leave->leaveType->name ?? 'Concediu',
                ];
            });

        $activeDelegations = Delegation::with(['employee', 'vehicle', 'stops', 'presenceEvent'])
            ->whereHas('presenceEvent', function ($query) {
                $query->active();
            })
            ->get()
            ->map(function ($delegation) {
                $stops = $delegation->stops;
                if ($stops->count() > 0) {
                    $firstStop = $stops->first();
                    $destination = $firstStop->name;
                    if ($stops->count() > 1) {
                        $destination .= ' +'.($stops->count() - 1);
                    }
                } else {
                    $destination = $delegation->name ?? 'External';
                }

                return [
                    'id' => $delegation->id,
                    'employee' => $delegation->employee->name ?? 'Unknown',
                    'avatar' => $delegation->employee->avatar_url ?? null,
                    'destination' => $destination,
                    'vehicle' => $delegation->vehicle ? $delegation->vehicle->license_plate : '-',
                    'since' => $delegation->presenceEvent->start_at->format('H:i'),
                ];
            });

        $totalEmployees = Employee::count();
        $presentCount = Employee::whereHas('presenceEvents', function ($q) {
            $q->where('type', 'presence')->active();
        })->count();

        return response()->json([
            'latest_logins' => $latestLogins,
            'on_leave' => $onLeave,
            'active_delegations' => $activeDelegations,
            'stats' => [
                'total_employees' => $totalEmployees,
                'present_count' => $presentCount,
                'active_delegations_count' => count($activeDelegations),
            ],
        ]);
    }

    public function getAllEmployeesStatus(): JsonResponse
    {
        return response()->json([
            'data' => $this->presenceService->getAllEmployeesStatus(),
        ]);
    }

    public function submitCode(Request $request): JsonResponse
    {
        $codeLength = tenant('code_length') ?? 3;
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:'.$codeLength],
            'flow' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['regular', 'delegation', 'leave'])],
            'workplace_id' => ['nullable'],
            'device_info' => ['nullable', 'array'],
        ]);

        $employee = Employee::where('workplace_enter_code', $validated['code'])->first();
        if (! $employee) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        try {
            return response()->json($this->presenceService->processKioskFlow($employee, $validated['flow'] ?? 'regular', $validated));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function endDelegationWithSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'schedule' => ['required', 'array'],
            'next_step' => ['nullable', 'string'],
        ]);

        $employee = Employee::where('workplace_enter_code', $validated['code'])->first();
        if (! $employee) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        try {
            $this->presenceService->endDelegationWithSchedule($employee, $validated['schedule']);

            // If next_step is provided, simulate the flow for that step
            if (! empty($validated['next_step'])) {
                if ($validated['next_step'] === 'regular') {
                    return response()->json([
                        'type' => 'checkin',
                        'message' => 'Delegația a fost încheiată. Acum ești înregistrat la locul de muncă.',
                        'employee' => $employee,
                        'time' => now()->format('H:i'),
                    ]);
                }

                return response()->json(
                    $this->presenceService->processKioskFlow($employee, $validated['next_step'], ['code' => $validated['code']])
                );
            }

            return response()->json(['type' => 'success', 'message' => 'Delegation ended successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function handleLateStart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'action' => ['required', 'in:start,end'],
            'time' => ['required', 'string', 'date_format:H:i'],
            'workplace_id' => ['required', 'exists:workplaces,id'],
        ]);

        $employee = Employee::where('workplace_enter_code', $validated['code'])->first();
        if (! $employee) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        try {
            $result = $this->presenceService->processLateStart($employee, $validated);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function handleShiftCorrection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'timeline' => ['required', 'array'],
            'timeline.*.date' => ['required', 'date_format:Y-m-d'],
            'timeline.*.start' => ['required', 'date_format:H:i'],
            'timeline.*.end' => ['required', 'date_format:H:i'],
        ]);

        $employee = Employee::where('workplace_enter_code', $validated['code'])->first();
        if (! $employee) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        try {
            $this->presenceService->correctShifts($employee, $validated['timeline']);

            return response()->json(['type' => 'success', 'message' => 'Shifts corrected successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancelDelegation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'presence_event_id' => ['required', 'exists:presence_events,id'],
        ]);

        $employee = Employee::where('workplace_enter_code', $validated['code'])->first();
        if (! $employee) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        try {
            $this->presenceService->cancelDelegation($employee, (int) $validated['presence_event_id']);

            return response()->json(['type' => 'success', 'message' => 'Delegation cancelled. Previous shift resumed.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function submitLeave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_days' => ['required', 'numeric'],
        ]);

        $employee = Employee::where('workplace_enter_code', $validated['code'])->first();
        if (! $employee) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        try {
            $leave = $this->presenceService->startLeave($employee, $validated);

            return response()->json(['type' => 'success', 'message' => 'Leave recorded.', 'leave' => $leave]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
