<?php

namespace App\Observers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "created" event.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status === 'APPROVED') {
            $this->adjustBalance($leaveRequest, 'deduct');
        }
    }

    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        // If status changed
        if ($leaveRequest->isDirty('status')) {
            $originalStatus = $leaveRequest->getOriginal('status');
            $newStatus = $leaveRequest->status;

            if ($originalStatus !== 'APPROVED' && $newStatus === 'APPROVED') {
                $this->adjustBalance($leaveRequest, 'deduct');
            } elseif ($originalStatus === 'APPROVED' && $newStatus !== 'APPROVED') {
                $this->adjustBalance($leaveRequest, 'refund');
            }
        }
    }

    /**
     * Handle the LeaveRequest "deleted" event.
     */
    public function deleted(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status === 'APPROVED') {
            $this->adjustBalance($leaveRequest, 'refund');
        }
    }

    /**
     * Adjust the user's leave balance.
     */
    protected function adjustBalance(LeaveRequest $leaveRequest, string $action): void
    {
        // Reload leave type to ensure we have the latest config
        $leaveRequest->loadMissing('leaveType');

        if (! $leaveRequest->leaveType || ! $leaveRequest->leaveType->affects_annual_quota) {
            return;
        }

        $year = $leaveRequest->start_date->year;

        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $leaveRequest->employee_id, 'year' => $year],
            ['total_entitlement' => 21] // Default entitlement if not exists
        );

        if ($action === 'deduct') {
            $balance->increment('taken', $leaveRequest->total_days);
        } elseif ($action === 'refund') {
            $balance->decrement('taken', $leaveRequest->total_days);
        }
    }
}
