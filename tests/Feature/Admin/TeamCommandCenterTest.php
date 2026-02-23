<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Tests\TenantTestCase;

class TeamCommandCenterTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['backpack.base.guard' => 'web']);

        if (LeaveType::count() == 0) {
             LeaveType::create(['name' => 'Annual', 'total_days' => 20]);
        }
    }

    public function test_team_command_center_loads_with_correct_stats_and_roster()
    {
        $admin = User::factory()->create();
        $adminEmployee = Employee::factory()->create(['user_id' => $admin->id]);

        $employee1 = User::factory()->create(['name' => 'Alice']);
        $emp1 = Employee::factory()->create(['user_id' => $employee1->id, 'first_name' => 'Alice']);

        $employee2 = User::factory()->create(['name' => 'Bob']);
        $emp2 = Employee::factory()->create(['user_id' => $employee2->id, 'first_name' => 'Bob']);

        Carbon::setTestNow(Carbon::parse('2023-10-27 10:00:00'));

        // Alice: Active today
        PresenceEvent::create([
            'employee_id' => $emp1->id,
            'workplace_id' => 1,
            'type' => 'presence',
            'start_at' => Carbon::parse('2023-10-27 09:00:00'),
            'start_method' => 'manual',
        ]);

        $leaveType = LeaveType::first();
        LeaveRequest::create([
            'employee_id' => $adminEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-10-30'),
            'end_date' => Carbon::parse('2023-10-31'),
            'total_days' => 2,
            'status' => 'APPROVED',
        ]);

        LeaveRequest::create([
            'employee_id' => $emp2->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-11-01'),
            'end_date' => Carbon::parse('2023-11-02'),
            'total_days' => 2,
            'status' => 'PENDING',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $url = 'http://' . $domain . '/admin/team-command-center';

        $response = $this->actingAs($admin)
            ->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.team_command_center');

        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['on_shift'], 'On Shift count mismatch');
        $this->assertEquals(0, $stats['on_delegation'], 'On Delegation count mismatch');
        $this->assertEquals(2, $stats['absent'], 'Absent count mismatch');
        $this->assertEquals(1, $stats['upcoming_leave'], 'Upcoming Leave count mismatch');

        $searchUrl = 'http://' . $domain . '/admin/team-command-center/search';
        $searchResponse = $this->actingAs($admin)->postJson($searchUrl);
        $searchResponse->assertStatus(200);

        $data = $searchResponse->json('data');
        $aliceRow = collect($data)->first(fn($row) => str_contains($row[0], 'Alice'));
        $this->assertNotNull($aliceRow, 'Alice not found in roster');
        $this->assertStringContainsString('Active', $aliceRow[1]);
        $this->assertStringContainsString('1h 00m', $aliceRow[4]);

        $actions = $response->viewData('actions');
        $this->assertCount(1, $actions['time_off_requests']);
        $this->assertTrue($actions['understaffed']);
    }
}
