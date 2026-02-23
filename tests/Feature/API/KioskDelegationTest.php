<?php

namespace Tests\Feature\API;

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Tenant;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class KioskDelegationTest extends TenantTestCase
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

    public function test_delegation_start_creates_checkin_if_not_present()
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/delegations", [
            'employee_id' => $this->employee->id,
            'name' => 'Delegation Site',
            'workplace_id' => $this->workplace->id,
        ]);

        $response->assertStatus(200);

        // Verify Auto Check-in
        $presence = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'presence')
            ->first();

        $this->assertNotNull($presence);
        $this->assertEquals($this->workplace->id, $presence->workplace_id);

        // Verify Delegation Event created
        $delegationEvent = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'delegation')
            ->first();

        $this->assertNotNull($delegationEvent);
        $this->assertTrue($presence->start_at->lt($delegationEvent->start_at));
    }

    public function test_delegation_start_does_not_create_extra_checkin_if_already_present()
    {
        // Manual check-in first
        PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'presence',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/delegations", [
            'employee_id' => $this->employee->id,
            'name' => 'Delegation Site',
            'workplace_id' => $this->workplace->id,
        ]);

        $response->assertStatus(200);

        // Still only 1 regular presence
        $this->assertEquals(1, PresenceEvent::where('type', 'presence')->count());
        
        $delegationEvent = PresenceEvent::where('type', 'delegation')->first();
        $this->assertNotNull($delegationEvent);
    }

    public function test_kiosk_code_ends_delegation_only_when_in_delegation()
    {
        // 1. Regular check-in
        PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'presence',
            'start_at' => now()->subHours(2),
            'start_method' => 'kiosk',
        ]);

        // 2. Start Delegation
        PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'delegation',
            'start_at' => now()->subHour(),
            'start_method' => 'kiosk',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '123',
            'flow' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJson(['type' => 'delegation_end']);

        // Delegation ended
        $delegationEvent = PresenceEvent::where('type', 'delegation')->first();
        $this->assertNotNull($delegationEvent->end_at);

        // Presence still active
        $presence = PresenceEvent::where('type', 'presence')->first();
        $this->assertNull($presence->end_at);
    }
}
