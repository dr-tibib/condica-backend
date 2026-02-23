<?php

use App\Models\PresenceEvent;
use App\Models\Employee;
use App\Models\User;
use App\Models\Workplace;
use App\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create(['id' => 'test']);
    $this->domain = $this->tenant->domains()->first()?->domain ?? $this->tenant->domains()->create(['domain' => 'test.localhost'])->domain;

    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    $this->token = $this->user->createToken('Test Device')->plainTextToken;
    $this->workplace = Workplace::factory()->create([
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'radius' => 100,
    ]);
});

// Check-in tests
it('can check in to a workplace', function () {
    $response = $this->withToken($this->token)->postJson(getUrl('/api/presence/check-in'), [
        'workplace_id' => $this->workplace->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'accuracy' => 10,
        'method' => 'manual',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Checked in successfully.',
        ]);

    expect(PresenceEvent::count())->toBe(1);
});

it('returns error when already checked in', function () {
    // First check-in
    PresenceEvent::create([
        'employee_id' => $this->employee->id,
        'workplace_id' => $this->workplace->id,
        'type' => 'presence',
        'start_at' => now(),
        'start_method' => 'manual',
    ]);

    // Try to check in again
    $response = $this->withToken($this->token)->postJson(getUrl('/api/presence/check-in'), [
        'workplace_id' => $this->workplace->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'method' => 'manual',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// Check-out tests
it('can check out from workplace', function () {
    // First check in
    $checkIn = PresenceEvent::create([
        'employee_id' => $this->employee->id,
        'workplace_id' => $this->workplace->id,
        'type' => 'presence',
        'start_at' => now()->subHour(),
        'start_method' => 'manual',
    ]);

    $response = $this->withToken($this->token)->postJson(getUrl('/api/presence/check-out'), [
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'accuracy' => 10,
        'method' => 'manual',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Checked out successfully.',
        ]);

    $checkIn->refresh();
    expect($checkIn->end_at)->not->toBeNull();
});

// Current status tests
it('returns current presence status when checked in', function () {
    $checkIn = PresenceEvent::create([
        'employee_id' => $this->employee->id,
        'workplace_id' => $this->workplace->id,
        'type' => 'presence',
        'start_at' => now()->subMinutes(30),
        'start_method' => 'manual',
    ]);

    $response = $this->withToken($this->token)->getJson(getUrl('/api/presence/current'));

    $response->assertSuccessful()
        ->assertJson([
            'is_present' => true,
            'current_workplace' => $this->workplace->name,
        ]);
});
