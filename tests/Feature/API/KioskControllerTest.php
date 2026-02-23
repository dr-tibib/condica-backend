<?php

namespace Tests\Feature\API;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

class KioskControllerTest extends TenantTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_submit_code_checkin_regular_flow()
    {
        $tenant = Tenant::first();
        $workplace = Workplace::factory()->create();
        $employee = Employee::factory()->create(['workplace_enter_code' => '123', 'workplace_id' => $workplace->id]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", ['code' => '123', 'flow' => 'regular']);

        $response->assertStatus(200)
            ->assertJson(['type' => 'checkin', 'message' => 'Checked in successfully.', 'employee' => ['name' => $employee->name]]);

        $this->assertDatabaseHas('presence_events', ['employee_id' => $employee->id, 'type' => 'presence', 'workplace_id' => $workplace->id]);
    }

    public function test_submit_code_checkout_regular_flow()
    {
        $tenant = Tenant::first();
        $workplace = Workplace::factory()->create();
        $employee = Employee::factory()->create(['workplace_enter_code' => '567', 'workplace_id' => $workplace->id]);

        $employee->presenceEvents()->create([
            'workplace_id' => $workplace->id,
            'type' => 'presence',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", ['code' => '567', 'flow' => 'regular']);

        $response->assertStatus(200)
            ->assertJson(['type' => 'checkout', 'message' => 'Checked out successfully.', 'employee' => ['name' => $employee->name]]);

        $event = $employee->presenceEvents()->first();
        $this->assertNotNull($event->end_at);
    }

    public function test_submit_code_invalid_code()
    {
        $tenant = Tenant::first();
        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", ['code' => '000']);
        $response->assertStatus(404);
    }

    public function test_submit_code_invalid_length_validation()
    {
        $tenant = Tenant::first();
        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", ['code' => '1234']);
        $response->assertStatus(422);
    }

    public function test_submit_code_delegation_flow()
    {
        $tenant = Tenant::first();
        $employee = Employee::factory()->create(['workplace_enter_code' => '999']);
        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", ['code' => '999', 'flow' => 'delegation']);
        $response->assertStatus(200);
    }

    public function test_config_endpoint_returns_defaults()
    {
        $tenant = Tenant::first();
        $domain = $tenant->domains->first()->domain;
        $response = $this->getJson("http://{$domain}/api/config");
        $response->assertStatus(200)->assertJson(['code_length' => 3]);
    }

    public function test_submit_code_delegation_flow_when_already_in_delegation()
    {
        $tenant = Tenant::first();
        $workplace = Workplace::factory()->create();
        $employee = Employee::factory()->create(['workplace_enter_code' => '888', 'workplace_id' => $workplace->id]);

        $employee->presenceEvents()->create([
            'workplace_id' => $workplace->id,
            'type' => 'delegation',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", ['code' => '888', 'flow' => 'delegation']);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Delegation ended, Shift started.', 'type' => 'checkin', 'employee' => ['name' => $employee->name]]);
    }

    public function test_dashboard_data_includes_stats()
    {
        $tenant = Tenant::first();
        $workplace = Workplace::factory()->create();

        $employee1 = Employee::factory()->create(['workplace_id' => $workplace->id]);
        $employee1->presenceEvents()->create([
            'workplace_id' => $workplace->id,
            'type' => 'presence',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $employee2 = Employee::factory()->create(['workplace_id' => $workplace->id]);
        $delegationEvent = $employee2->presenceEvents()->create([
            'workplace_id' => $workplace->id,
            'type' => 'delegation',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);
        
        $delegation = \App\Models\Delegation::create([
             'employee_id' => $employee2->id,
             'presence_event_id' => $delegationEvent->id,
             'name' => 'Trip',
        ]);
        
        $delegationEvent->update([
            'linkable_id' => $delegation->id,
            'linkable_type' => \App\Models\Delegation::class,
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->getJson("http://{$domain}/api/kiosk/dashboard");

        $response->assertStatus(200);
        $stats = $response->json('stats');
        $this->assertEquals(1, $stats['present_count']);
        $this->assertEquals(1, $stats['active_delegations_count']);
    }
}
