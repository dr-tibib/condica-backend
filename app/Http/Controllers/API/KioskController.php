<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DelegationPlace;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\User;
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

    public function getDashboardData(): JsonResponse
    {
        // 1. Latest Logins
        $latestLogins = PresenceEvent::with('user')
            ->whereIn('event_type', ['check_in', 'check_out', 'delegation_start', 'delegation_end'])
            ->orderBy('event_time', 'desc')
            ->take(20)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'user' => $event->user->name,
                    'time' => $event->event_time->format('H:i'),
                    'type' => $event->event_type,
                ];
            });

        // 2. On Leave
        $onLeave = LeaveRequest::with('user')
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'user' => $leave->user->name,
                    'until' => $leave->end_date->format('d.m.Y'),
                ];
            });

        // 3. Active Delegations
        $activeDelegations = Delegation::with(['user', 'vehicle', 'delegationPlace'])
            ->whereHas('startEvent', function ($query) {
                $query->whereNull('pair_event_id');
            })
            ->get()
            ->map(function ($delegation) {
                $destination = $delegation->delegationPlace ? $delegation->delegationPlace->name : ($delegation->address ?? $delegation->name);
                return [
                    'id' => $delegation->id,
                    'user' => $delegation->user->name,
                    'destination' => $destination,
                    'vehicle' => $delegation->vehicle ? $delegation->vehicle->license_plate : '-',
                ];
            });

        return response()->json([
            'latest_logins' => $latestLogins,
            'on_leave' => $onLeave,
            'active_delegations' => $activeDelegations,
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

        $user = User::where('workplace_enter_code', $code)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid code.',
            ], 404);
        }

        if ($flow === 'delegation' || $flow === 'concediu') {
            $latestEvent = $user->latestPresenceEvent;
            $isDelegated = $latestEvent && $latestEvent->isDelegationStart();

            if ($flow === 'delegation' && $isDelegated) {
                // End delegation
                $event = $this->presenceService->delegationEndOnly($user, [
                    'method' => 'kiosk',
                    'device_info' => $validated['device_info'] ?? null,
                ]);

                return response()->json([
                    'message' => 'Delegation ended successfully.',
                    'type' => 'delegation_end',
                    'user' => [
                        'name' => $user->name,
                    ],
                    'time' => $event->event_time->format('g:i A'),
                    'event' => $event,
                ]);
            }

            return response()->json([
                'message' => 'User verified.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'default_workplace_id' => $user->default_workplace_id,
                ],
                'is_delegated' => $isDelegated,
                'current_delegation' => $isDelegated ? $latestEvent : null,
            ]);
        }

        // Regular flow: Check In / Check Out
        try {
            if ($user->isCurrentlyPresent()) {
                $latestEvent = $user->latestPresenceEvent;

                if ($latestEvent && $latestEvent->isDelegationStart()) {
                    // Only end the delegation, do not check out completely
                    $event = $this->presenceService->delegationEndOnly($user, [
                        'method' => 'kiosk',
                        'device_info' => $validated['device_info'] ?? null,
                    ]);
                    $message = 'Delegation ended successfully.';
                    $type = 'delegation_end';
                } else {
                    // Check Out
                    $event = $this->presenceService->checkOut($user, [
                        'method' => 'kiosk',
                        'device_info' => $validated['device_info'] ?? null,
                    ]);
                    $message = 'Checked out successfully.';
                    $type = 'checkout';
                }
            } else {
                // Check In
                // For regular flow, we need a workplace ID.
                // Assuming the kiosk is associated with a workplace, passed in request or config.
                // If not provided in request, check user's default workplace or fail.

                $workplaceId = $validated['workplace_id'] ?? $user->default_workplace_id;

                if (! $workplaceId) {
                     return response()->json([
                        'message' => 'No workplace configured for this check-in.',
                    ], 400);
                }

                $event = $this->presenceService->checkIn($user, [
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
                'user' => [
                    'name' => $user->name,
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
        $tenantData = tenant()->data ?? [];
        $codeLength = (int) ($tenantData['code_length'] ?? 3);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'code' => ['required', 'string', "digits:{$codeLength}"],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $user = User::find($validated['user_id']);

        if ($user->workplace_enter_code !== $validated['code']) {
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
            'user_id' => $validated['user_id'],
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
}
