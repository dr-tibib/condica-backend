<?php

namespace Tests\Feature\API;

use App\Models\Employee;
use App\Models\Delegation;
use App\Models\PresenceEvent;
use Tests\TenantTestCase;

class DelegationTest extends TenantTestCase
{
    public function test_can_start_delegation()
    {
        $employee = Employee::factory()->create(['workplace_enter_code' => '123']);
        $url = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';

        $response = $this->postJson($url, [
            'employee_id' => $employee->id,
            'name' => 'Client Site',
            'place_id' => 'abc-123',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'delegation-start');

        $this->assertDatabaseHas('delegations', [
            'employee_id' => $employee->id,
            'place_id' => 'abc-123',
            'name' => 'Client Site',
        ]);

        $this->assertDatabaseHas('presence_events', [
            'employee_id' => $employee->id,
            'event_type' => 'delegation_start',
            'workplace_id' => null,
        ]);

        $this->assertTrue($employee->fresh()->isCurrentlyPresent());
    }

    public function test_submit_code_ends_delegation_via_flow_param()
    {
         $employee = Employee::factory()->create(['workplace_enter_code' => '123']);
         $delegationUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';
         $kioskUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/kiosk/submit-code';

         // Start delegation
         $this->postJson($delegationUrl, [
            'employee_id' => $employee->id,
            'name' => 'Client Site',
         ]);

         // If in delegation, flow=delegation should end it
         $response = $this->postJson($kioskUrl, [
             'code' => '123',
             'flow' => 'delegation',
         ]);

         $response->assertStatus(200)
            ->assertJson([
                'type' => 'delegation_end',
                'message' => 'Delegation ended successfully.',
            ]);
    }

    public function test_submit_code_ends_delegation_via_regular_flow()
    {
         $employee = Employee::factory()->create(['workplace_enter_code' => '123']);
         $delegationUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';
         $kioskUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/kiosk/submit-code';

         // Start delegation
         $this->postJson($delegationUrl, [
            'employee_id' => $employee->id,
            'name' => 'Client Site',
         ]);

         // Regular check-in flow (no flow param, or default)
         // Should act as Delegation End if currently delegated
         $response = $this->postJson($kioskUrl, [
             'code' => '123',
             'device_info' => ['foo' => 'bar'],
         ]);

         if ($response->status() !== 200) {
             dump($response->json());
         }

         $response->assertStatus(200)
            ->assertJson([
                'type' => 'delegation_end',
            ]);

         $this->assertDatabaseHas('presence_events', [
            'employee_id' => $employee->id,
            'event_type' => 'delegation_end',
         ]);
    }

    public function test_list_recent_delegations()
    {
         $employee = Employee::factory()->create();
         $url = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';

         Delegation::create([
            'employee_id' => $employee->id,
            'name' => 'Place A',
            'place_id' => 'place-a',
         ]);

         Delegation::create([
            'employee_id' => $employee->id,
            'name' => 'Place B',
            'place_id' => 'place-b',
         ]);

         // Duplicate place
         Delegation::create([
            'employee_id' => $employee->id,
            'name' => 'Place A',
            'place_id' => 'place-a',
         ]);

         $response = $this->getJson($url);

         $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Should be unique
    }
}
