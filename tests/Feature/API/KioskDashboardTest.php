<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\DelegationPlace;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PresenceEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class KioskDashboardTest extends TenantTestCase
{
    use RefreshDatabase;

    public function test_get_dashboard_data_returns_correct_structure()
    {
        $tenant = Tenant::first();
        $domain = $tenant->domains->first()->domain;

        // 1. Setup Logins (Check-ins)
        $workplace = Workplace::factory()->create();
        $user1 = User::factory()->create(['name' => 'User One']);
        $event1 = PresenceEvent::create([
            'user_id' => $user1->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subMinutes(10),
            'method' => 'manual',
        ]);

        // 1.1 Setup Check-out
        $user1b = User::factory()->create(['name' => 'User One B']);
        PresenceEvent::create([
            'user_id' => $user1b->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_out',
            'event_time' => now()->subMinutes(5),
            'method' => 'manual',
        ]);

        // 2. Setup Leave
        $user2 = User::factory()->create(['name' => 'User Two']);
        $leaveType = LeaveType::create([
            'name' => 'Test Leave',
            'is_paid' => true,
        ]);

        LeaveRequest::create([
            'user_id' => $user2->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'APPROVED',
            'leave_type_id' => $leaveType->id,
            'total_days' => 3,
        ]);

        // 3. Setup Delegation
        $user3 = User::factory()->create(['name' => 'User Three']);
        $vehicle = Vehicle::create(['name' => 'Car', 'license_plate' => 'B-123-TST']);
        $place = DelegationPlace::create(['name' => 'Test Place', 'google_place_id' => '123']);

        $startEvent = PresenceEvent::create([
            'user_id' => $user3->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => now()->subMinutes(30),
            'method' => 'kiosk',
        ]);

        Delegation::create([
            'user_id' => $user3->id,
            'start_event_id' => $startEvent->id,
            'name' => 'Test Delegation',
            'vehicle_id' => $vehicle->id,
            'delegation_place_id' => $place->id,
        ]);

        // 3.1 Setup Delegation End
        $user3b = User::factory()->create(['name' => 'User Three B']);
        PresenceEvent::create([
            'user_id' => $user3b->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'delegation_end',
            'event_time' => now()->subMinutes(15),
            'method' => 'kiosk',
        ]);

        $response = $this->getJson("http://{$domain}/api/kiosk/dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'latest_logins',
                'on_leave',
                'active_delegations',
            ]);

        // Check Logins
        $response->assertJsonFragment([
            'user' => 'User One',
            'type' => 'check_in',
        ]);

        $response->assertJsonFragment([
            'user' => 'User One B',
            'type' => 'check_out',
        ]);

        $response->assertJsonFragment([
            'user' => 'User Three B',
            'type' => 'delegation_end',
        ]);

        // Check Leave
        $response->assertJsonFragment([
            'user' => 'User Two',
        ]);

        // Check Delegation
        $response->assertJsonFragment([
            'user' => 'User Three',
            'destination' => 'Test Place',
            'vehicle' => 'B-123-TST',
        ]);
    }
}
