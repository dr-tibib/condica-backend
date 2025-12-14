<?php

use App\Models\PresenceEvent;
use App\Models\User;
use App\Models\Workplace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('Test Device')->plainTextToken;
    $this->workplace = Workplace::factory()->create([
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'radius' => 100,
    ]);
});

// Check-in tests
it('can check in to a workplace', function () {
    $response = $this->withToken($this->token)->postJson('/api/presence/check-in', [
        'workplace_id' => $this->workplace->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'accuracy' => 10,
        'method' => 'manual',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Checked in successfully.',
        ])
        ->assertJsonStructure([
            'event' => ['id', 'event_type', 'event_time', 'workplace'],
        ]);

    expect(PresenceEvent::count())->toBe(1);
    expect(PresenceEvent::first()->event_type)->toBe('check_in');
});

it('returns validation error when workplace_id is missing', function () {
    $response = $this->withToken($this->token)->postJson('/api/presence/check-in', [
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['workplace_id']);
});

it('returns validation error when location is missing', function () {
    $response = $this->withToken($this->token)->postJson('/api/presence/check-in', [
        'workplace_id' => $this->workplace->id,
        'method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('returns validation error when method is missing', function () {
    $response = $this->withToken($this->token)->postJson('/api/presence/check-in', [
        'workplace_id' => $this->workplace->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['method']);
});

it('returns error when checking in outside geofence', function () {
    $response = $this->withToken($this->token)->postJson('/api/presence/check-in', [
        'workplace_id' => $this->workplace->id,
        'latitude' => 41.0000, // Far away
        'longitude' => -75.0000,
        'method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['location']);
});

it('returns error when already checked in', function () {
    // First check-in
    PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => now(),
    ]);

    // Try to check in again
    $response = $this->withToken($this->token)->postJson('/api/presence/check-in', [
        'workplace_id' => $this->workplace->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('requires authentication to check in', function () {
    $response = $this->postJson('/api/presence/check-in', [
        'workplace_id' => $this->workplace->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'method' => 'manual',
    ]);

    $response->assertUnauthorized();
});

// Check-out tests
it('can check out from workplace', function () {
    // First check in
    $checkIn = PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => now()->subHour(),
    ]);

    $response = $this->withToken($this->token)->postJson('/api/presence/check-out', [
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'accuracy' => 10,
        'method' => 'manual',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Checked out successfully.',
        ]);

    expect(PresenceEvent::where('event_type', 'check_out')->count())->toBe(1);

    $checkOut = PresenceEvent::where('event_type', 'check_out')->first();
    expect($checkOut->pair_event_id)->toBe($checkIn->id);

    // Verify bidirectional pairing
    $checkIn->refresh();
    expect($checkIn->pair_event_id)->toBe($checkOut->id);
});

it('returns error when checking out without check-in', function () {
    $response = $this->withToken($this->token)->postJson('/api/presence/check-out', [
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('returns error when checking out outside geofence', function () {
    PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => now()->subHour(),
    ]);

    $response = $this->withToken($this->token)->postJson('/api/presence/check-out', [
        'latitude' => 41.0000, // Far away
        'longitude' => -75.0000,
        'method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['location']);
});

it('requires authentication to check out', function () {
    $response = $this->postJson('/api/presence/check-out', [
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'method' => 'manual',
    ]);

    $response->assertUnauthorized();
});

// Current status tests
it('returns current presence status when checked in', function () {
    $checkIn = PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => now()->subMinutes(30),
    ]);

    $response = $this->withToken($this->token)->getJson('/api/presence/current');

    $response->assertSuccessful()
        ->assertJson([
            'is_present' => true,
            'current_workplace' => $this->workplace->name,
        ]);

    expect($response->json('duration_minutes'))->toBeGreaterThan(29);
});

it('returns not present when no events exist', function () {
    $response = $this->withToken($this->token)->getJson('/api/presence/current');

    $response->assertSuccessful()
        ->assertJson([
            'is_present' => false,
            'latest_event' => null,
            'current_workplace' => null,
            'duration_minutes' => null,
        ]);
});

it('returns not present when last event was check-out', function () {
    PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_out',
        'event_time' => now()->subMinutes(10),
    ]);

    $response = $this->withToken($this->token)->getJson('/api/presence/current');

    $response->assertSuccessful()
        ->assertJson([
            'is_present' => false,
        ]);
});

it('requires authentication to get current status', function () {
    $response = $this->getJson('/api/presence/current');

    $response->assertUnauthorized();
});

// History tests
it('returns paginated presence history', function () {
    PresenceEvent::factory()->count(25)->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/presence/history');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'event_type', 'event_time', 'workplace'],
            ],
            'meta' => ['current_page', 'total'],
        ]);

    expect(count($response->json('data')))->toBe(20); // Default pagination
});

it('returns empty history when no events exist', function () {
    $response = $this->withToken($this->token)->getJson('/api/presence/history');

    $response->assertSuccessful()
        ->assertJson(['data' => []]);
});

it('requires authentication to get history', function () {
    $response = $this->getJson('/api/presence/history');

    $response->assertUnauthorized();
});

// Today's sessions tests
it('returns todays sessions with total minutes', function () {
    // Complete session: 1 hour
    $checkIn1 = PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => today()->addHours(9),
    ]);

    $checkOut1 = PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_out',
        'event_time' => today()->addHours(10),
        'pair_event_id' => $checkIn1->id,
    ]);

    $checkIn1->update(['pair_event_id' => $checkOut1->id]);

    // Another complete session: 2 hours
    $checkIn2 = PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => today()->addHours(14),
    ]);

    $checkOut2 = PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_out',
        'event_time' => today()->addHours(16),
        'pair_event_id' => $checkIn2->id,
    ]);

    $checkIn2->update(['pair_event_id' => $checkOut2->id]);

    $response = $this->withToken($this->token)->getJson('/api/presence/today');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'date',
            'total_minutes',
            'sessions' => [
                '*' => ['check_in', 'check_out', 'duration_minutes'],
            ],
        ]);

    expect($response->json('total_minutes'))->toBe(180); // 3 hours
    expect(count($response->json('sessions')))->toBe(2);
});

it('returns ongoing session in todays data', function () {
    PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_type' => 'check_in',
        'event_time' => today()->addHours(9),
    ]);

    $response = $this->withToken($this->token)->getJson('/api/presence/today');

    $response->assertSuccessful();

    $sessions = $response->json('sessions');
    expect(count($sessions))->toBe(1);
    expect($sessions[0]['check_out'])->toBeNull();
    expect($sessions[0]['duration_minutes'])->toBeGreaterThan(0);
});

it('returns empty sessions when no events today', function () {
    PresenceEvent::factory()->create([
        'user_id' => $this->user->id,
        'workplace_id' => $this->workplace->id,
        'event_time' => now()->subDays(2),
    ]);

    $response = $this->withToken($this->token)->getJson('/api/presence/today');

    $response->assertSuccessful()
        ->assertJson([
            'total_minutes' => 0,
            'sessions' => [],
        ]);
});

it('requires authentication to get today', function () {
    $response = $this->getJson('/api/presence/today');

    $response->assertUnauthorized();
});
