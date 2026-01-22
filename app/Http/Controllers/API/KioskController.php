<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService
    ) {}

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
            'flow' => ['nullable', 'string', 'in:regular,delegation'],
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

        if ($flow === 'delegation') {
            return response()->json([
                'message' => 'User verified.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'default_workplace_id' => $user->default_workplace_id,
                ],
            ]);
        }

        // Regular flow: Check In / Check Out
        try {
            if ($user->isCurrentlyPresent()) {
                // Check Out
                $event = $this->presenceService->checkOut($user, [
                    'method' => 'kiosk',
                    'device_info' => $validated['device_info'] ?? null,
                ]);
                $message = 'Checked out successfully.';
                $type = 'checkout';
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

    /**
     * Start delegation (check-in at specific location).
     */
    public function startDelegation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'workplace_id' => ['required', 'exists:workplaces,id'],
            'device_info' => ['nullable', 'array'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        try {
            $event = $this->presenceService->checkIn($user, [
                'workplace_id' => $validated['workplace_id'],
                'method' => 'kiosk',
                'device_info' => $validated['device_info'] ?? null,
                'notes' => 'Delegation Start',
            ]);

            return response()->json([
                'message' => 'Delegation started successfully.',
                'type' => 'delegation-start',
                'user' => ['name' => $user->name],
                'time' => $event->event_time->format('g:i A'),
                'event' => $event,
            ]);

        } catch (\Exception $e) {
            Log::error('Kiosk delegation error: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
