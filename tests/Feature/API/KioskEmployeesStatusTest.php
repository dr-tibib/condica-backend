<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Tenant;
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

        // 1. Present
        $employee1 = Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Present']);
        PresenceEvent::create([
            'employee_id' => $employee1->id,
            'workplace_id' => $workplace->id,
            'type' => 'presence',
            'start_at' => now()->subMinutes(10),
            'start_method' => 'kiosk',
        ]);

        // 2. In Delegation
        $employee2 = Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Delegation']);
        $startEvent = PresenceEvent::create([
            'employee_id' => $employee2->id,
            'workplace_id' => $workplace->id,
            'type' => 'delegation',
            'start_at' => now()->subMinutes(30),
            'start_method' => 'kiosk',
        ]);
        
        $delegation = Delegation::create([
            'employee_id' => $employee2->id,
            'presence_event_id' => $startEvent->id,
            'name' => 'Client Site',
        ]);
        
        $startEvent->update([
            'linkable_id' => $delegation->id,
            'linkable_type' => Delegation::class,
        ]);

        $response = $this->getJson("http://{$domain}/api/kiosk/employees/status");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'John Present', 'status' => 'present'])
            ->assertJsonFragment(['name' => 'Jane Delegation', 'status' => 'delegation', 'details' => 'Client Site']);
    }
}
