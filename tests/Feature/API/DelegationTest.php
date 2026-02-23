<?php

namespace Tests\Feature\API;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\Workplace;
use App\Models\Delegation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class DelegationTest extends TenantTestCase
{
    use RefreshDatabase;

    public function test_can_start_delegation()
    {
        $tenant = Tenant::first();
        $employee = Employee::factory()->create(['workplace_id' => null]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/delegations", [
            'employee_id' => $employee->id,
            'places' => [
                ['place_id' => 'abc-123', 'name' => 'Client Site A'],
                ['place_id' => 'def-456', 'name' => 'Client Site B'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Delegation started successfully.',
                'delegation' => [
                    'name' => 'Client Site A',
                ],
            ]);

        $this->assertDatabaseHas('presence_events', [
            'employee_id' => $employee->id,
            'type' => 'delegation',
        ]);

        $this->assertDatabaseHas('delegation_stops', [
            'place_id' => 'abc-123',
            'name' => 'Client Site A',
        ]);

        $this->assertDatabaseHas('delegation_stops', [
            'place_id' => 'def-456',
            'name' => 'Client Site B',
        ]);
    }

    public function test_submit_code_ends_delegation_via_flow_param()
    {
        $tenant = Tenant::first();
        $employee = Employee::factory()->create([
            'workplace_enter_code' => '777'
        ]);

        // Start delegation
        $employee->presenceEvents()->create([
            'type' => 'delegation',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '777',
            'flow' => 'delegation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'type' => 'checkin',
                'message' => 'Delegation ended, Shift started.',
            ]);

        $event = $employee->presenceEvents()->where('type', 'delegation')->first();
        $this->assertNotNull($event->end_at);
    }

    public function test_submit_code_ends_delegation_via_regular_flow()
    {
        $tenant = Tenant::first();
        $employee = Employee::factory()->create([
            'workplace_enter_code' => '111'
        ]);

        // Start delegation
        $employee->presenceEvents()->create([
            'type' => 'delegation',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '111',
            'flow' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'type' => 'checkin',
                'message' => 'Delegation ended, Shift started.',
            ]);

         $event = $employee->presenceEvents()->where('type', 'delegation')->first();
         $this->assertNotNull($event->end_at);
    }

    public function test_list_recent_delegations()
    {
        $tenant = Tenant::first();
        $employee = Employee::factory()->create();

        $delegation = Delegation::create([
            'employee_id' => $employee->id,
            'name' => 'Header Name',
        ]);

        \App\Models\DelegationStop::create([
            'delegation_id' => $delegation->id,
            'place_id' => 'p1',
            'name' => 'Recent Place',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->getJson("http://{$domain}/api/delegations?employee_id={$employee->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['place_id' => 'p1']);
    }
}
