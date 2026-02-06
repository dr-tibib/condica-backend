<?php

namespace Tests\Feature\API;

use App\Models\Tenant;
use App\Models\User;
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
        // Note: Tenant configuration update for 'code_length' is skipped due to test environment persistence issues.
        // We test with default length (3).

        $workplace = Workplace::factory()->create();
        $user = User::factory()->create([
            'workplace_enter_code' => '123',
            'default_workplace_id' => $workplace->id,
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '123',
            'flow' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'type' => 'checkin',
                'message' => 'Checked in successfully.',
                'user' => ['name' => $user->name],
            ]);

        $this->assertDatabaseHas('presence_events', [
            'user_id' => $user->id,
            'event_type' => 'check_in',
            'workplace_id' => $workplace->id,
        ]);
    }

    public function test_submit_code_checkout_regular_flow()
    {
        $tenant = Tenant::first();

        $workplace = Workplace::factory()->create();
        $user = User::factory()->create([
            'workplace_enter_code' => '567',
            'default_workplace_id' => $workplace->id,
        ]);

        // Check in first
        $user->presenceEvents()->create([
            'workplace_id' => $workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subHour(),
            'method' => 'kiosk',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '567',
            'flow' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'type' => 'checkout',
                'message' => 'Checked out successfully.',
                'user' => ['name' => $user->name],
            ]);

        $this->assertDatabaseHas('presence_events', [
            'user_id' => $user->id,
            'event_type' => 'check_out',
        ]);
    }

    public function test_submit_code_invalid_code()
    {
        $tenant = Tenant::first();
        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '000',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Invalid code.']);
    }

    public function test_submit_code_invalid_length_validation()
    {
        $tenant = Tenant::first();
        // Default length is 3

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '1234', // Too long (4 digits)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_submit_code_delegation_flow()
    {
        $tenant = Tenant::first();

        $user = User::factory()->create([
            'workplace_enter_code' => '999',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '999',
            'flow' => 'delegation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User verified.',
                'user' => ['id' => $user->id, 'name' => $user->name],
            ]);

        // Verify no check-in happened
        $this->assertDatabaseMissing('presence_events', [
            'user_id' => $user->id,
        ]);
    }

    public function test_config_endpoint_returns_defaults()
    {
        $tenant = Tenant::first();

        $domain = $tenant->domains->first()->domain;
        $response = $this->getJson("http://{$domain}/api/config");

        $response->assertStatus(200)
            ->assertJson(['code_length' => 3]);
    }

    public function test_submit_code_delegation_flow_when_already_in_delegation()
    {
        $tenant = Tenant::first();
        $workplace = Workplace::factory()->create();
        $user = User::factory()->create([
            'workplace_enter_code' => '888',
            'default_workplace_id' => $workplace->id,
        ]);

        // Start delegation
        $user->presenceEvents()->create([
            'workplace_id' => $workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => now()->subHour(),
            'method' => 'kiosk',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->postJson("http://{$domain}/api/kiosk/submit-code", [
            'code' => '888',
            'flow' => 'delegation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Delegation ended successfully.',
                'type' => 'delegation_end',
                'user' => ['name' => $user->name],
            ]);

        $this->assertDatabaseHas('presence_events', [
            'user_id' => $user->id,
            'event_type' => 'delegation_end',
        ]);
    }
}
