<?php

namespace App\Services\Dashboard\Alerts\Providers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\Dashboard\Alerts\Alert;
use App\Services\Dashboard\Alerts\AlertProvider;
use Illuminate\Support\Collection;

class RejectedLeaveProvider implements AlertProvider
{
    public function getAlerts(Employee $employee): Collection
    {
        $rejected = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'REJECTED')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->get();

        return $rejected->map(function ($request) {
            return new Alert(
                'Request Rejected',
                'Sick leave request rejected.', // You could make this dynamic based on leave type
                backpack_url('leave-request/' . $request->id . '/show'),
                'View Reason',
                'warning'
            );
        });
    }
}
