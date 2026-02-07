<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    /**
     * Get current user balance.
     */
    public function balance(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);
        $employee = $request->user()->employee;

        if (!$employee) {
             abort(404, 'Employee profile not found.');
        }

        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            ['total_entitlement' => 21, 'carried_over' => 0, 'taken' => 0]
        );

        return response()->json($balance);
    }

    /**
     * Submit new request.
     */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'medical_code' => 'nullable|string',
            'attachment_path' => 'nullable|string',
        ]);

        $employee = $request->user()->employee;
        if (!$employee) {
             abort(404, 'Employee profile not found.');
        }

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        // Overlap prevention
        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<', $start)
                            ->where('end_date', '>', $end);
                    });
            })
            ->whereIn('status', ['PENDING', 'APPROVED'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['dates' => 'Leave request overlaps with existing records.']);
        }

        $type = LeaveType::findOrFail($data['leave_type_id']);

        if ($type->medical_code_required && empty($data['medical_code'])) {
            throw ValidationException::withMessages(['medical_code' => 'Medical code is required for sick leave.']);
        }

        $totalDays = $this->calculateTotalDays($start, $end);

        // Balance Check
        if ($type->affects_annual_quota) {
            $year = $start->year;

            $balance = LeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'year' => $year],
                ['total_entitlement' => 21]
            );

            $available = ($balance->total_entitlement + $balance->carried_over) - $balance->taken;

            $pendingDays = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'PENDING')
                ->whereHas('leaveType', function ($q) {
                    $q->where('affects_annual_quota', true);
                })
                ->whereYear('start_date', $year)
                ->sum('total_days');

            if ($totalDays > ($available - $pendingDays)) {
                throw ValidationException::withMessages(['balance' => 'Insufficient leave balance.']);
            }
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => $totalDays,
            'status' => 'PENDING',
            'medical_code' => $data['medical_code'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
        ]);

        return response()->json($leaveRequest, 201);
    }

    /**
     * Manager approval action.
     */
    public function approve(Request $request): JsonResponse
    {
        if (! $request->user()->can('approve leave')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'request_id' => 'required|exists:leave_requests,id',
            'status' => 'required|in:APPROVED,REJECTED',
            'reason' => 'required_if:status,REJECTED|string|nullable',
        ]);

        $leaveRequest = LeaveRequest::findOrFail($data['request_id']);

        // Check if status is already set to prevent double accounting
        if ($leaveRequest->status !== 'PENDING') {
             throw ValidationException::withMessages(['status' => 'Request is already processed.']);
        }

        $leaveRequest->status = $data['status'];
        if ($data['status'] === 'REJECTED') {
            $leaveRequest->rejection_reason = $data['reason'];
        }
        $leaveRequest->approver_id = $request->user()->employee->id;
        $leaveRequest->save();
        // Balance update is handled by LeaveRequestObserver

        return response()->json($leaveRequest);
    }

    /**
     * Get team availability.
     */
    public function teamCalendar(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $requests = LeaveRequest::where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<', $start)
                            ->where('end_date', '>', $end);
                    });
            })
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->with('employee:id,first_name,last_name,email')
            ->get();

        return response()->json($requests);
    }

    /**
     * Payroll data dump.
     */
    public function exportPayroll(Request $request)
    {
        if (! $request->user()->can('export payroll')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $format = $request->input('format', 'csv');

        // This usually covers start date in month, or active in month?
        // "containing: Employee Name, Leave Type, Start Date, End Date..."
        // Probably all approved leaves that overlap with the month.

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $requests = LeaveRequest::where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                     ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<', $start)
                            ->where('end_date', '>', $end);
                    });
            })
            ->where('status', 'APPROVED')
            ->with(['employee', 'leaveType'])
            ->get();

        if ($format === 'csv') {
            $csv = "Employee Name,Leave Type,Start Date,End Date,Medical Code\n";
            foreach ($requests as $req) {
                $csv .= "{$req->employee->name},{$req->leaveType->name},{$req->start_date->toDateString()},{$req->end_date->toDateString()},{$req->medical_code}\n";
            }
            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"payroll_{$year}_{$month}.csv\"");
        }

        return response()->json($requests);
    }

    /**
     * Calculate total business days excluding weekends and public holidays.
     */
    private function calculateTotalDays(Carbon $start, Carbon $end): float
    {
        $days = 0;
        $period = CarbonPeriod::create($start, $end);

        // Get holidays within range
        $holidays = PublicHoliday::whereBetween('date', [$start->startOfDay(), $end->endOfDay()])
            ->get()
            ->map(fn ($h) => $h->date->format('Y-m-d'))
            ->toArray();

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }
            if (in_array($date->format('Y-m-d'), $holidays)) {
                continue;
            }
            $days++;
        }
        return $days;
    }
}
