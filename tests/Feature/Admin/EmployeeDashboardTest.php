<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\PresenceEvent;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\LeaveType;
use Carbon\Carbon;
use Tests\TenantTestCase;

class EmployeeDashboardTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fix for Backpack guard issue in tests
        config(['backpack.base.guard' => 'web']);

        // Required for leave requests
        if (LeaveType::count() == 0) {
             LeaveType::create(['name' => 'Annual', 'total_days' => 20]);
        }
    }

    public function test_dashboard_loads_with_correct_data()
    {
        // 1. Create User
        $user = User::factory()->create();

        // 2. Setup Data
        // Assume today is Wednesday 15th (middle of month)
        Carbon::setTestNow(Carbon::parse('2023-11-15 12:00:00'));

        // Check in/out for previous days
        // Day 1: 8h
        $checkIn = PresenceEvent::factory()->create([
            'user_id' => $user->id,
            'event_type' => 'check_in',
            'event_time' => Carbon::parse('2023-11-01 09:00:00'),
        ]);
        PresenceEvent::factory()->create([
            'user_id' => $user->id,
            'event_type' => 'check_out',
            'event_time' => Carbon::parse('2023-11-01 17:00:00'),
            'pair_event_id' => $checkIn->id,
        ]);

        // - Missing Clock Out (Alert)
        // Yesterday check-in without check-out
        PresenceEvent::factory()->create([
            'user_id' => $user->id,
            'event_type' => 'check_in',
            'event_time' => Carbon::parse('2023-11-14 09:00:00'),
        ]);

        // - Rejected Leave (Alert)
        $leaveType = LeaveType::first();
        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-11-10'),
            'end_date' => Carbon::parse('2023-11-11'),
            'total_days' => 2,
            'status' => 'REJECTED',
            'updated_at' => Carbon::now(),
        ]);

        // - Upcoming Leave
        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-11-20'),
            'end_date' => Carbon::parse('2023-11-21'),
            'total_days' => 2,
            'status' => 'APPROVED',
        ]);

        // - Upcoming Holiday
        PublicHoliday::create([
            'date' => Carbon::parse('2023-11-30'),
            'description' => 'St. Andrew',
        ]);

        // 3. Authenticate and Visit
        $response = $this->actingAs($user)
            ->get(route('backpack.dashboard'));

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.employee');

        // Assert View Data
        $metrics = $response->viewData('metrics');
        $this->assertEquals(8.0, $metrics['logged_hours']); // Only 1 completed day (8h)

        // Assert Alerts
        $response->assertSee('Missing clock-out on Nov 14th');
        $response->assertSee('Request Rejected');

        // Assert Upcoming
        $response->assertSee('Next Leave (Approved)');
        $response->assertSee('Nov 20');
        $response->assertSee('St. Andrew');
    }
}
