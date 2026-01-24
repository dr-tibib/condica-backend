<?php

namespace Tests\Feature\Admin;

use App\Models\User;
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
        // 1. Create Users
        $admin = User::factory()->create();
        $employee1 = User::factory()->create(['name' => 'Alice']);
        $employee2 = User::factory()->create(['name' => 'Bob']);

        // 2. Setup Data
        Carbon::setTestNow(Carbon::parse('2023-10-27 10:00:00')); // Friday 10 AM

        // Employee 1: On Shift (Check In today)
        PresenceEvent::factory()->create([
            'user_id' => $employee1->id,
            'event_type' => 'check_in',
            'event_time' => Carbon::parse('2023-10-27 09:00:00'),
        ]);

        // Employee 2: Absent (No events today)

        // Upcoming Leave for Admin
        $leaveType = LeaveType::first();
        LeaveRequest::create([
            'user_id' => $admin->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-10-30'), // Next Monday
            'end_date' => Carbon::parse('2023-10-31'),
            'total_days' => 2,
            'status' => 'APPROVED',
        ]);

        // Pending Leave Request (Action Center)
        LeaveRequest::create([
            'user_id' => $employee2->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::parse('2023-11-01'),
            'end_date' => Carbon::parse('2023-11-02'),
            'total_days' => 2,
            'status' => 'PENDING',
        ]);

        // 3. Authenticate and Visit
        $domain = $this->tenant->domains->first()->domain;
        $url = 'http://' . $domain . '/admin/team-command-center';

        $response = $this->actingAs($admin)
            ->get($url);

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard.team_command_center');

        // Check Stats
        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['on_shift'], 'On Shift count mismatch'); // Alice
        $this->assertEquals(0, $stats['on_delegation'], 'On Delegation count mismatch');
        // Absent: Admin (no checkin) + Bob = 2. Alice is present.
        // Total 3 users. 1 present. 2 absent.
        $this->assertEquals(2, $stats['absent'], 'Absent count mismatch');
        $this->assertEquals(1, $stats['upcoming_leave'], 'Upcoming Leave count mismatch'); // Admin's leave

        // Check Roster
        $roster = $response->viewData('roster');
        $this->assertCount(3, $roster);

        // Find Alice in roster
        $aliceData = $roster->first(fn($r) => $r['user']->id === $employee1->id);
        $this->assertEquals('Active', $aliceData['status']);
        $this->assertEquals('1h 00m', $aliceData['actual_hours']);

        // Check Actions
        $actions = $response->viewData('actions');
        $this->assertCount(1, $actions['time_off_requests']);
        $this->assertTrue($actions['understaffed']); // Target 4, Current 1
    }
}
