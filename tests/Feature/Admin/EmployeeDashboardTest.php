<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Employee;
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
        config(['backpack.base.guard' => 'web']);
        if (LeaveType::count() == 0) {
             LeaveType::create(['name' => 'Annual', 'total_days' => 20]);
        }
    }

    public function test_dashboard_loads_with_correct_data()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        Carbon::setTestNow(Carbon::parse('2023-11-15 12:00:00'));

        // Completed day: 8h
        PresenceEvent::create([
            'employee_id' => $employee->id,
            'workplace_id' => 1,
            'type' => 'presence',
            'start_at' => Carbon::parse('2023-11-01 09:00:00'),
            'end_at' => Carbon::parse('2023-11-01 17:00:00'),
            'start_method' => 'manual',
            'end_method' => 'manual',
        ]);

        // Missing Clock Out (Alert)
        PresenceEvent::create([
            'employee_id' => $employee->id,
            'workplace_id' => 1,
            'type' => 'presence',
            'start_at' => Carbon::parse('2023-11-14 09:00:00'),
            'start_method' => 'manual',
        ]);

        $leaveType = LeaveType::first();
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-11-10'),
            'end_date' => Carbon::parse('2023-11-11'),
            'total_days' => 2,
            'status' => 'REJECTED',
            'updated_at' => Carbon::now(),
        ]);

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-11-20'),
            'end_date' => Carbon::parse('2023-11-21'),
            'total_days' => 2,
            'status' => 'APPROVED',
        ]);

        PublicHoliday::create([
            'date' => Carbon::parse('2023-11-30'),
            'description' => 'St. Andrew',
        ]);

        $response = $this->actingAs($user)
            ->get(route('backpack.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.employee');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(8.0, $metrics['logged_hours']);

        $response->assertSee('Missing clock-out on Nov 14th');
        $response->assertSee('Request Rejected');
        $response->assertSee('Next Leave (Approved)');
        $response->assertSee('Nov 20');
        $response->assertSee('St. Andrew');
    }
}
