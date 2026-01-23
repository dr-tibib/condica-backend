<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\PresenceEvent;
use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TenantTestCase;

class KioskDelegationTest extends TenantTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workplace $workplace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'workplace_enter_code' => '123',
        ]);

        $this->workplace = Workplace::create([
            'name' => 'HQ',
            'is_active' => true,
        ]);
    }

    public function test_delegation_start_creates_checkin_if_not_present()
    {
        $this->assertFalse($this->user->isCurrentlyPresent());

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/delegations";

        $response = $this->postJson($url, [
            'user_id' => $this->user->id,
            'name' => 'Client Site',
            'place_id' => 'place_123',
            'workplace_id' => $this->workplace->id, // Passing workplace_id from kiosk
        ]);

        $response->assertStatus(200);

        // Verify Check-in created
        $checkIn = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'check_in')
            ->first();

        $this->assertNotNull($checkIn);
        $this->assertEquals($this->workplace->id, $checkIn->workplace_id);

        // Verify Delegation Start created
        $delegationStart = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'delegation_start')
            ->first();

        $this->assertNotNull($delegationStart);
        $this->assertEquals('Delegation at Client Site', $delegationStart->notes);

        // Verify timing (checkin should be before delegation start)
        $this->assertTrue($checkIn->event_time->lt($delegationStart->event_time));
    }

    public function test_delegation_start_does_not_create_extra_checkin_if_already_present()
    {
        // Manually check in
        PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subHour(),
            'method' => 'kiosk',
        ]);

        $this->assertTrue($this->user->refresh()->isCurrentlyPresent());

        $initialEventsCount = PresenceEvent::count();

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/delegations";

        $response = $this->postJson($url, [
            'user_id' => $this->user->id,
            'name' => 'Client Site',
            'place_id' => 'place_123',
            // workplace_id might be sent but should be ignored or consistent
            'workplace_id' => $this->workplace->id,
        ]);

        $response->assertStatus(200);

        // Should have added only 1 event (delegation_start)
        $this->assertEquals($initialEventsCount + 1, PresenceEvent::count());

        $delegationStart = PresenceEvent::latest('id')->first();
        $this->assertEquals('delegation_start', $delegationStart->event_type);
    }

    public function test_kiosk_code_ends_delegation_only_when_in_delegation()
    {
        // Start Delegation (which implies check-in)
        // 1. Check in
        $checkIn = PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subHours(2),
            'method' => 'kiosk',
        ]);

        // 2. Start Delegation
        $delegationStart = PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => now()->subHour(),
            'method' => 'kiosk',
            'notes' => 'Delegation',
        ]);

        // Also create Delegation record as Controller does
        Delegation::create([
             'user_id' => $this->user->id,
             'name' => 'Somewhere',
             'start_event_id' => $delegationStart->id,
        ]);

        // Submit code (regular flow)
        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/kiosk/submit-code";

        $response = $this->postJson($url, [
            'code' => '123',
            'workplace_id' => $this->workplace->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['type' => 'delegation_end']);

        // Verify delegation_end event created
        $delegationEnd = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'delegation_end')
            ->latest('id')
            ->first();

        $this->assertNotNull($delegationEnd);
        $this->assertEquals($delegationStart->id, $delegationEnd->pair_event_id);

        // Update delegation start pairing
        $delegationStart->refresh();
        $this->assertEquals($delegationEnd->id, $delegationStart->pair_event_id);

        // Verify NO check_out event (user still present)
        $checkOut = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'check_out')
            ->first();

        $this->assertNull($checkOut);

        // Verify user is still "present" (latest event is delegation_end, but base check_in is open?
        // Wait, isCurrentlyPresent checks if latest event is check_in or delegation_start.
        // If latest is delegation_end, isCurrentlyPresent might return false?
        // Let's check User::isCurrentlyPresent logic.
    }
}
