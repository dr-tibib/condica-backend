<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TenantTestCase;

class KioskDelegationEndTest extends TenantTestCase
{
    use RefreshDatabase;

    protected Employee $employee;
    protected Workplace $workplace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workplace = Workplace::create([
            'name' => 'HQ',
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'workplace_enter_code' => '123',
            'workplace_id' => $this->workplace->id,
        ]);
    }

    public function test_delegation_end_regular_flow_triggers_schedule_for_long_delegations()
    {
        // Start delegation 30 hours ago
        $start = now()->subHours(30);

        $delegationStart = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => $start,
            'method' => 'kiosk',
            'notes' => 'Long Delegation',
        ]);

        Delegation::create([
             'employee_id' => $this->employee->id,
             'name' => 'Long Trip',
             'start_event_id' => $delegationStart->id,
             'start_date' => $start,
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/kiosk/submit-code";

        // Regular flow (default flow is regular)
        $response = $this->postJson($url, [
            'code' => '123',
            'workplace_id' => $this->workplace->id,
        ]);

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200);

        // This is what we expect after the fix
        $response->assertJson([
            'type' => 'delegation_end_schedule_required',
            'delegation_start_time' => $start->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_delegation_end_delegation_flow_triggers_schedule_for_long_delegations()
    {
        // Start delegation 30 hours ago
        $start = now()->subHours(30);

        $delegationStart = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => $start,
            'method' => 'kiosk',
            'notes' => 'Long Delegation',
        ]);

        Delegation::create([
             'employee_id' => $this->employee->id,
             'name' => 'Long Trip',
             'start_event_id' => $delegationStart->id,
             'start_date' => $start,
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/kiosk/submit-code";

        // Delegation flow
        $response = $this->postJson($url, [
            'code' => '123',
            'flow' => 'delegation',
            'workplace_id' => $this->workplace->id,
        ]);

        $response->assertStatus(200);

        // This currently works according to the user
        $response->assertJson([
            'type' => 'delegation_end_schedule_required',
            'delegation_start_time' => $start->format('Y-m-d H:i:s'),
        ]);
    }
}
