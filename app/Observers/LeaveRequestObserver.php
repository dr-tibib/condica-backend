<?php

namespace App\Observers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PresenceEvent;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "created" event.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status === 'APPROVED') {
            $this->adjustBalance($leaveRequest, 'deduct');
            $this->syncPresenceEvent($leaveRequest);
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
                $this->syncPresenceEvent($leaveRequest);
            } elseif ($originalStatus === 'APPROVED' && $newStatus !== 'APPROVED') {
                $this->adjustBalance($leaveRequest, 'refund');
                $this->removePresenceEvent($leaveRequest);
            }
        } elseif ($leaveRequest->status === 'APPROVED' && ($leaveRequest->isDirty('start_date') || $leaveRequest->isDirty('end_date'))) {
            $this->syncPresenceEvent($leaveRequest);
        }
    }

    /**
     * Handle the LeaveRequest "deleted" event.
     */
    public function deleted(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status === 'APPROVED') {
            $this->adjustBalance($leaveRequest, 'refund');
            $this->removePresenceEvent($leaveRequest);
        }
    }

    /**
     * Sync presence event for approved leave.
     */
    protected function syncPresenceEvent(LeaveRequest $leaveRequest): void
    {
        PresenceEvent::updateOrCreate(
            [
                'linkable_id' => $leaveRequest->id,
                'linkable_type' => LeaveRequest::class,
            ],
            [
                'employee_id' => $leaveRequest->employee_id,
                'type' => 'leave',
                'start_at' => $leaveRequest->start_date->startOfDay(),
                'end_at' => $leaveRequest->end_date->endOfDay(),
                'notes' => 'Approved leave: ' . ($leaveRequest->leaveType->name ?? 'Leave'),
            ]
        );
    }

    /**
     * Remove presence event for leave.
     */
    protected function removePresenceEvent(LeaveRequest $leaveRequest): void
    {
        PresenceEvent::where('linkable_id', $leaveRequest->id)
            ->where('linkable_type', LeaveRequest::class)
            ->delete();
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
