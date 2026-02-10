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

class KioskEmployeesStatusTest extends TenantTestCase
{
    use RefreshDatabase;

    public function test_get_employees_status_returns_correct_data()
    {
        $tenant = Tenant::first();
        $domain = $tenant->domains->first()->domain;
        $workplace = Workplace::factory()->create();

        // 1. Present Employee
        $user1 = User::factory()->create();
        $employee1 = Employee::factory()->create(['user_id' => $user1->id, 'first_name' => 'Present', 'last_name' => 'Employee']);
        PresenceEvent::create([
            'employee_id' => $employee1->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subMinutes(10),
            'method' => 'manual',
        ]);

        // 2. Absent Employee
        $user2 = User::factory()->create();
        $employee2 = Employee::factory()->create(['user_id' => $user2->id, 'first_name' => 'Absent', 'last_name' => 'Employee']);

        // 3. On Leave Employee
        $user3 = User::factory()->create();
        $employee3 = Employee::factory()->create(['user_id' => $user3->id, 'first_name' => 'Leave', 'last_name' => 'Employee']);
        $leaveType = LeaveType::create(['name' => 'Test Leave', 'is_paid' => true]);
        LeaveRequest::create([
            'employee_id' => $employee3->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'APPROVED',
            'leave_type_id' => $leaveType->id,
            'total_days' => 3,
        ]);

        // 4. Delegation Employee
        $user4 = User::factory()->create();
        $employee4 = Employee::factory()->create(['user_id' => $user4->id, 'first_name' => 'Delegation', 'last_name' => 'Employee']);
        $vehicle = Vehicle::create(['name' => 'Car', 'license_plate' => 'B-123-TST']);
        $place = DelegationPlace::create(['name' => 'Test Place', 'google_place_id' => '123']);
        $startEvent = PresenceEvent::create([
            'employee_id' => $employee4->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => now()->subMinutes(30),
            'method' => 'kiosk',
        ]);
        Delegation::create([
            'employee_id' => $employee4->id,
            'start_event_id' => $startEvent->id,
            'name' => 'Test Delegation',
            'vehicle_id' => $vehicle->id,
            'delegation_place_id' => $place->id,
        ]);

        $response = $this->getJson("http://{$domain}/api/kiosk/employees/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar',
                        'status',
                        'details',
                    ]
                ]
            ]);

        // Present
        $response->assertJsonFragment([
            'name' => 'Present Employee',
            'status' => 'present',
        ]);

        // Absent
        $response->assertJsonFragment([
            'name' => 'Absent Employee',
            'status' => 'absent',
            'details' => null,
        ]);

        // Leave
        $response->assertJsonFragment([
            'name' => 'Leave Employee',
            'status' => 'leave',
        ]);

        // Delegation
        $response->assertJsonFragment([
            'name' => 'Delegation Employee',
            'status' => 'delegation',
            'details' => 'Test Place',
        ]);
    }
}
