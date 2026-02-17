<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DelegationPlace;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\Vehicle;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService
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

    public function getAllEmployeesStatus(): JsonResponse
    {
        $employees = Employee::with([
            'latestCheckinCheckoutPresenceEvent',
            'latestPresenceEvent',
            'leaveRequests' => function ($query) {
                $query->where('status', 'APPROVED')
                      ->whereDate('start_date', '<=', now())
                      ->whereDate('end_date', '>=', now());
            },
            'delegations' => function ($query) {
                 $query->whereHas('startEvent', function ($q) {
                    $q->whereNull('pair_event_id');
                });
            }
        ])
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get()
        ->map(function ($employee) {
            $status = 'absent';
            $details = null;

            // Check Leave
            if ($employee->leaveRequests->isNotEmpty()) {
                $status = 'leave';
                $details = 'Concediu până la ' . $employee->leaveRequests->first()->end_date->format('d.m');
            }
            // Check Delegation
            elseif ($employee->delegations->isNotEmpty()) {
                $status = 'delegation';
                $delegation = $employee->delegations->first();
                $destination = $delegation->delegationPlace ? $delegation->delegationPlace->name : ($delegation->address ?? $delegation->name ?? 'Delegatie');
                $details = $destination;
            }
            // Check Present
            elseif ($employee->isCurrentlyPresent()) {
                $status = 'present';
                $lastEvent = $employee->latestCheckinCheckoutPresenceEvent;
                $details = 'Prezent de la ' . ($lastEvent ? $lastEvent->event_time->format('H:i') : '-');
            }

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'avatar' => $employee->avatar_url,
                'status' => $status,
                'details' => $details,
            ];
        });

        return response()->json([
            'data' => $employees,
        ]);
    }

    public function getDashboardData(): JsonResponse
    {
        // 1. Latest Logins
        $latestLogins = PresenceEvent::with('employee')
            ->whereIn('event_type', ['check_in', 'check_out', 'delegation_start', 'delegation_end'])
            ->orderBy('event_time', 'desc')
            ->take(20)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'employee' => $event->employee->name ?? 'Unknown',
                    'time' => $event->event_time->format('H:i'),
                    'type' => $event->event_type,
                ];
            });

        // 2. On Leave
        $onLeave = LeaveRequest::with('employee')
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee' => $leave->employee->name ?? 'Unknown',
                    'until' => $leave->end_date->format('d.m.Y'),
                ];
            });

        // 3. Active Delegations
        $activeDelegationsQuery = Delegation::with(['employee', 'vehicle', 'delegationPlace'])
            ->whereHas('startEvent', function ($query) {
                $query->whereNull('pair_event_id');
            });

        $activeDelegationsCount = $activeDelegationsQuery->count();

        $activeDelegations = $activeDelegationsQuery->get()
            ->map(function ($delegation) {
                $destination = $delegation->delegationPlace ? $delegation->delegationPlace->name : ($delegation->address ?? $delegation->name);
                return [
                    'id' => $delegation->id,
                    'employee' => $delegation->employee->name ?? 'Unknown',
                    'destination' => $destination,
                    'vehicle' => $delegation->vehicle ? $delegation->vehicle->license_plate : '-',
                ];
            });

        // 4. Counts
        $totalEmployees = Employee::count();

        // Present Count
        $presentCount = Employee::with('latestCheckinCheckoutPresenceEvent')->get()
            ->filter(function ($employee) {
                return $employee->isCurrentlyPresent();
            })
            ->count();

        return response()->json([
            'latest_logins' => $latestLogins,
            'on_leave' => $onLeave,
            'active_delegations' => $activeDelegations,
            'stats' => [
                'total_employees' => $totalEmployees,
                'present_count' => $presentCount,
                'active_delegations_count' => $activeDelegationsCount,
            ]
        ]);
    }

    /**
     * Verify code and perform check-in/check-out or return user details.
     */
    public function submitCode(Request $request): JsonResponse
    {
        // Get configured code length, default to 3
        $tenantData = tenant()->data ?? [];
        $codeLength = (int) ($tenantData['code_length'] ?? 3);

        $validated = $request->validate([
            'code' => ['required', 'string', "digits:{$codeLength}"],
            'flow' => ['nullable', 'string', 'in:regular,delegation,concediu'],
            'workplace_id' => ['nullable', 'exists:workplaces,id'],
            'device_info' => ['nullable', 'array'],
        ]);

        $code = $validated['code'];
        $flow = $validated['flow'] ?? 'regular';

        $employee = Employee::where('workplace_enter_code', $code)->first();

        if (! $employee) {
            return response()->json([
                'message' => 'Invalid code.',
            ], 404);
        }

        if ($flow === 'delegation' || $flow === 'concediu') {
            $latestEvent = $employee->latestPresenceEvent;
            $isDelegated = $latestEvent && $latestEvent->isDelegationStart();

            if ($flow === 'delegation' && $isDelegated) {
                return $this->handleDelegationEnd($employee, $validated['device_info'] ?? null);
            }

            return response()->json([
                'message' => 'User verified.',
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'default_workplace_id' => $employee->workplace_id,
                ],
                'is_delegated' => $isDelegated,
                'current_delegation' => $isDelegated ? $latestEvent : null,
            ]);
        }

        // Regular flow: Check In / Check Out
        try {
            $latestEvent = $employee->latestPresenceEvent;
            $isDelegated = $latestEvent && $latestEvent->isDelegationStart();

            if ($isDelegated) {
                return $this->handleDelegationEnd($employee, $validated['device_info'] ?? null);
            } elseif ($employee->isCurrentlyPresent()) {
                // Check Out
                $event = $this->presenceService->checkOut($employee, [
                    'method' => 'kiosk',
                    'device_info' => $validated['device_info'] ?? null,
                ]);
                $message = 'Checked out successfully.';
                $type = 'checkout';
            } else {
                // Check In
                $workplaceId = $validated['workplace_id'] ?? $employee->workplace_id;

                if (! $workplaceId) {
                     return response()->json([
                        'message' => 'No workplace configured for this check-in.',
                    ], 400);
                }

                $event = $this->presenceService->checkIn($employee, [
                    'workplace_id' => $workplaceId,
                    'method' => 'kiosk',
                    'device_info' => $validated['device_info'] ?? null,
                ]);
                $message = 'Checked in successfully.';
                $type = 'checkin';
            }

            return response()->json([
                'message' => $message,
                'type' => $type,
                'employee' => [
                    'name' => $employee->name,
                ],
                'time' => $event->event_time->format('g:i A'),
                'event' => $event,
            ]);

        } catch (\Exception $e) {
            Log::error('Kiosk check-in/out error: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function storeLeaveRequest(Request $request): JsonResponse
    {
        // Get configured code length, default to 3
        $tenantData = tenant()->data ?? [];
        $codeLength = (int) ($tenantData['code_length'] ?? 3);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'code' => ['required', 'string', "digits:{$codeLength}"],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $employee = Employee::find($validated['employee_id']);

        if ($employee->workplace_enter_code !== $validated['code']) {
            return response()->json(['message' => 'Invalid code.'], 403);
        }

        // Find default leave type (e.g. Concediu de Odihna)
        $leaveType = LeaveType::where('affects_annual_quota', true)->first()
            ?? LeaveType::first();

        if (!$leaveType) {
            return response()->json(['message' => 'No leave types configured.'], 500);
        }

        // Calculate total days (naive implementation, manager can adjust)
        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $diff = $start->diffInDays($end) + 1;

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $leaveType->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $diff,
            'status' => 'APPROVED',
        ]);

        return response()->json([
            'message' => 'Leave request created successfully.',
            'data' => $leaveRequest,
        ]);
    }
          
    public function endDelegationWithSchedule(Request $request): JsonResponse
    {
        // Get configured code length, default to 3
        $tenantData = tenant()->data ?? [];
        $codeLength = (int) ($tenantData['code_length'] ?? 3);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'schedule' => ['required', 'array'],
            'schedule.*.date' => ['required', 'date_format:Y-m-d'],
            'schedule.*.start_time' => ['required', 'date_format:H:i'],
            'schedule.*.end_time' => ['required', 'date_format:H:i'],
            'code' => ['required', 'string', "digits:{$codeLength}"],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        if ($employee->workplace_enter_code !== $validated['code']) {
            return response()->json([
                'message' => 'Invalid code.',
            ], 403);
        }

        try {
            $this->presenceService->endDelegationWithSchedule($employee, $validated['schedule']);

            return response()->json([
                'message' => 'Delegation ended successfully.',
                'type' => 'delegation_end',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function handleDelegationEnd(Employee $employee, ?array $deviceInfo): JsonResponse
    {
        $latestEvent = $employee->latestPresenceEvent;

        // Check duration for long delegations
        $start = $latestEvent->event_time;
        $now = now();
        $durationHours = $start->diffInHours($now);

        if ($durationHours > 24) {
            // Fetch Shift Settings
            $shiftStart = \Backpack\Settings\app\Models\Setting::get('shift_start') ?? '08:00';
            $shiftEnd = \Backpack\Settings\app\Models\Setting::get('shift_end') ?? '17:00';

            // Generate Dates
            $dates = [];
            $period = \Carbon\CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $now->copy()->startOfDay());

            foreach ($period as $date) {
                $d = $date->format('Y-m-d');
                $defaultStart = $shiftStart;
                $defaultEnd = $shiftEnd;

                if ($date->isSameDay($start)) {
                    $defaultStart = $start->format('H:i');
                }
                if ($date->isSameDay($now)) {
                    $defaultEnd = $now->format('H:i');
                }

                $dates[] = [
                    'date' => $d,
                    'start' => $defaultStart,
                    'end' => $defaultEnd,
                ];
            }

            return response()->json([
                'message' => 'Delegation spans multiple days.',
                'type' => 'delegation_end_schedule_required',
                'employee' => [
                    'name' => $employee->name,
                    'id' => $employee->id,
                ],
                'delegation_start_time' => $start->format('Y-m-d H:i:s'),
                'schedule_days' => $dates,
                'shift_settings' => [
                    'start' => $shiftStart,
                    'end' => $shiftEnd,
                ],
            ]);
        }

        // End delegation normal flow
        $event = $this->presenceService->delegationEndOnly($employee, [
            'method' => 'kiosk',
            'device_info' => $deviceInfo,
        ]);

        return response()->json([
            'message' => 'Delegation ended successfully.',
            'type' => 'delegation_end',
            'employee' => [
                'name' => $employee->name,
            ],
            'time' => $event->event_time->format('g:i A'),
            'event' => $event,
        ]);
    }

}
