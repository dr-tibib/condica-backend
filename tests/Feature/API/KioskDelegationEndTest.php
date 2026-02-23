<?php

namespace Tests\Feature\API;

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Tenant;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class KioskDelegationEndTest extends TenantTestCase
{
    use RefreshDatabase;

    protected $employee;
    protected $workplace;
    

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::first();
        $this->workplace = Workplace::factory()->create();
        $this->employee = Employee::factory()->create([
            'workplace_id' => $this->workplace->id,
            'workplace_enter_code' => '123',
        ]);
    }

    public function test_delegation_end_regular_flow_triggers_schedule_for_long_delegations()
    {
        $start = now()->subDays(2);
        
        $delegationStart = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'delegation',
            'start_at' => $start,
            'start_method' => 'kiosk',
            'notes' => 'Long Delegation',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '123',
            'flow' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'type' => 'delegation_end_schedule_required',
            ]);
    }

    public function test_delegation_end_delegation_flow_triggers_schedule_for_long_delegations()
    {
        $start = now()->subDays(2);

        $delegationStart = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'delegation',
            'start_at' => $start,
            'start_method' => 'kiosk',
            'notes' => 'Long Delegation',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '123',
            'flow' => 'delegation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'type' => 'delegation_end_schedule_required',
            ]);
    }
}
