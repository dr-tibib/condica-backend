<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Delegation;
use App\Models\PresenceEvent;
use Tests\TenantTestCase;

class DelegationTest extends TenantTestCase
{
    public function test_can_start_delegation()
    {
        $user = User::factory()->create(['workplace_enter_code' => '123']);
        $url = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';

        $response = $this->postJson($url, [
            'user_id' => $user->id,
            'name' => 'Client Site',
            'place_id' => 'abc-123',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'delegation-start');

        $this->assertDatabaseHas('delegations', [
            'user_id' => $user->id,
            'place_id' => 'abc-123',
            'name' => 'Client Site',
        ]);

        $this->assertDatabaseHas('presence_events', [
            'user_id' => $user->id,
            'event_type' => 'delegation_start',
            'workplace_id' => null,
        ]);

        $this->assertTrue($user->fresh()->isCurrentlyPresent());
    }

    public function test_submit_code_returns_delegation_status()
    {
         $user = User::factory()->create(['workplace_enter_code' => '123']);
         $delegationUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';
         $kioskUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/kiosk/submit-code';

         // Start delegation
         $this->postJson($delegationUrl, [
            'user_id' => $user->id,
            'name' => 'Client Site',
         ]);

         $response = $this->postJson($kioskUrl, [
             'code' => '123',
             'flow' => 'delegation',
         ]);

         $response->assertStatus(200)
            ->assertJson([
                'is_delegated' => true,
            ]);
    }

    public function test_submit_code_ends_delegation()
    {
         $user = User::factory()->create(['workplace_enter_code' => '123']);
         $delegationUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';
         $kioskUrl = 'http://' . $this->tenant->domains->first()->domain . '/api/kiosk/submit-code';

         // Start delegation
         $this->postJson($delegationUrl, [
            'user_id' => $user->id,
            'name' => 'Client Site',
         ]);

         // Regular check-in flow (no flow param, or default)
         // Should act as check-out (Delegation End)
         $response = $this->postJson($kioskUrl, [
             'code' => '123',
             'device_info' => ['foo' => 'bar'],
         ]);

         if ($response->status() !== 200) {
             dump($response->json());
         }

         $response->assertStatus(200)
            ->assertJson([
                'type' => 'checkout',
            ]);

         $this->assertDatabaseHas('presence_events', [
            'user_id' => $user->id,
            'event_type' => 'delegation_end',
         ]);

         $this->assertFalse($user->fresh()->isCurrentlyPresent());
    }

    public function test_list_recent_delegations()
    {
         $user = User::factory()->create();
         $url = 'http://' . $this->tenant->domains->first()->domain . '/api/delegations';

         Delegation::create([
            'user_id' => $user->id,
            'name' => 'Place A',
            'place_id' => 'place-a',
         ]);

         Delegation::create([
            'user_id' => $user->id,
            'name' => 'Place B',
            'place_id' => 'place-b',
         ]);

         // Duplicate place
         Delegation::create([
            'user_id' => $user->id,
            'name' => 'Place A',
            'place_id' => 'place-a',
         ]);

         $response = $this->getJson($url);

         $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Should be unique
    }
}
