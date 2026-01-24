<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\User;
use Carbon\Carbon;
use Tests\TenantTestCase;

class TeamCommandCenterTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['backpack.base.guard' => 'web']);
    }

    public function test_team_command_center_loads_with_widgets()
    {
        // 1. Setup Data
        // User 1: On Shift (Checked In)
        $user1 = User::factory()->create(['name' => 'User One']);
        PresenceEvent::create([
            'user_id' => $user1->id,
            'event_type' => 'check_in',
            'event_time' => Carbon::today()->setHour(9),
            'method' => 'manual',
        ]);

        // User 2: On Delegation
        $user2 = User::factory()->create(['name' => 'User Two']);
        PresenceEvent::create([
            'user_id' => $user2->id,
            'event_type' => 'delegation_start',
            'event_time' => Carbon::today()->setHour(10),
            'method' => 'manual',
        ]);

        // User 3: On Leave
        $user3 = User::factory()->create(['name' => 'User Three']);
        $leaveType = LeaveType::create(['name' => 'Annual Leave', 'medical_code_required' => false, 'affects_annual_quota' => true]);
        LeaveRequest::create([
            'user_id' => $user3->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today(),
            'status' => 'APPROVED',
            'total_days' => 1,
        ]);

        // User 4: Absent (Created but no event, no leave)
        $user4 = User::factory()->create(['name' => 'User Four']);

        // User 5: Upcoming Leave (Next week)
        $user5 = User::factory()->create(['name' => 'User Five']);
        LeaveRequest::create([
            'user_id' => $user5->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'status' => 'APPROVED',
            'total_days' => 2,
        ]);

        // Authenticate
        $admin = User::factory()->create(['is_global_superadmin' => true]);

        $url = 'http://' . $this->tenant->domains->first()->domain . '/admin/team-command-center';

        $response = $this->actingAs($admin, 'web')->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.team_command_center');

        // Check for Widgets
        $response->assertSee('On Shift');
        $response->assertSee('On Delegation');
        $response->assertSee('Absent / Late');
        $response->assertSee('Upcoming Time Off');

        // Check for progress bar classes which indicate the widget type is used
        $response->assertSee('progress-bar bg-success');
        $response->assertSee('progress-bar bg-primary');
        $response->assertSee('progress-bar bg-danger');
        $response->assertSee('progress-bar bg-warning');
    }

    public function test_attendance_sheet_view_renders_correctly()
    {
        // Authenticate
        $admin = User::factory()->create(['is_global_superadmin' => true]);

        $url = 'http://' . $this->tenant->domains->first()->domain . '/admin/team-command-center/export';

        $response = $this->actingAs($admin, 'web')->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.attendance_sheet');

        // Assert some view content to ensure it's the right file and basic structure is there
        $response->assertSee('FOAIA COLECTIVĂ DE PREZENȚĂ');
        $response->assertSee('Nume și Prenume');
    }
}
