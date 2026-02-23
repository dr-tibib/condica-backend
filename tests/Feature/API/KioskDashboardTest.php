<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\DelegationPlace;
use App\Models\Employee;
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

        $workplace = Workplace::factory()->create();
        $user1 = User::factory()->create();
        $employee1 = Employee::factory()->create(['user_id' => $user1->id, 'first_name' => 'User', 'last_name' => 'One']);

        PresenceEvent::create([
            'employee_id' => $employee1->id,
            'workplace_id' => $workplace->id,
            'type' => 'presence',
            'start_at' => now()->subMinutes(10),
            'start_method' => 'manual',
        ]);

        $user1b = User::factory()->create();
        $employee1b = Employee::factory()->create(['user_id' => $user1b->id, 'first_name' => 'User', 'last_name' => 'One B']);

        PresenceEvent::create([
            'employee_id' => $employee1b->id,
            'workplace_id' => $workplace->id,
            'type' => 'presence',
            'start_at' => now()->subMinutes(5),
            'end_at' => now(),
            'start_method' => 'manual',
            'end_method' => 'manual',
        ]);

        $user2 = User::factory()->create();
        $employee2 = Employee::factory()->create(['user_id' => $user2->id, 'first_name' => 'User', 'last_name' => 'Two']);

        $leaveType = LeaveType::create(['name' => 'Test Leave', 'is_paid' => true]);

        LeaveRequest::create([
            'employee_id' => $employee2->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'APPROVED',
            'leave_type_id' => $leaveType->id,
            'total_days' => 3,
        ]);

        $user3 = User::factory()->create();
        $employee3 = Employee::factory()->create(['user_id' => $user3->id, 'first_name' => 'User', 'last_name' => 'Three']);

        $vehicle = Vehicle::create(['name' => 'Car', 'license_plate' => 'B-123-TST']);
        $place = DelegationPlace::create(['name' => 'Test Place', 'google_place_id' => '123']);

        $startEvent = PresenceEvent::create([
            'employee_id' => $employee3->id,
            'workplace_id' => $workplace->id,
            'type' => 'delegation',
            'start_at' => now()->subMinutes(30),
            'start_method' => 'kiosk',
        ]);

        $delegation = Delegation::create([
            'employee_id' => $employee3->id,
            'presence_event_id' => $startEvent->id,
            'name' => 'Test Delegation',
            'vehicle_id' => $vehicle->id,
            'delegation_place_id' => $place->id,
        ]);
        
        $startEvent->update([
            'linkable_id' => $delegation->id,
            'linkable_type' => Delegation::class,
        ]);

        $user3b = User::factory()->create();
        $employee3b = Employee::factory()->create(['user_id' => $user3b->id, 'first_name' => 'User', 'last_name' => 'Three B']);

        PresenceEvent::create([
            'employee_id' => $employee3b->id,
            'workplace_id' => $workplace->id,
            'type' => 'delegation',
            'start_at' => now()->subMinutes(15),
            'end_at' => now(),
            'start_method' => 'kiosk',
            'end_method' => 'kiosk',
        ]);

        $response = $this->getJson("http://{$domain}/api/kiosk/dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure(['latest_logins', 'on_leave', 'active_delegations']);

        $response->assertJsonFragment(['employee' => 'User One', 'type' => 'check_in']);
        $response->assertJsonFragment(['employee' => 'User One B', 'type' => 'check_out']);
        $response->assertJsonFragment(['employee' => 'User Three B', 'type' => 'delegation_end']);
    }
}
