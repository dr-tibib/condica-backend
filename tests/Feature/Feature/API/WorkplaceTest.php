<?php

use App\Models\User;
use App\Models\Workplace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('Test Device')->plainTextToken;
});

it('can list all active workplaces', function () {
    Workplace::factory()->count(3)->create(['is_active' => true]);
    Workplace::factory()->create(['is_active' => false]); // Should not appear

    $response = $this->withToken($this->token)->getJson('/api/workplaces');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('returns workplaces with distance when location provided', function () {
    $workplace = Workplace::factory()->create([
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'is_active' => true,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/workplaces?latitude=40.7128&longitude=-74.0060');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'city', 'county', 'street_address', 'country', 'location', 'distance'],
            ],
        ]);

    expect($response->json('data.0.distance'))->toBeLessThan(1); // Should be very close
});

it('sorts workplaces by distance when location provided', function () {
    $near = Workplace::factory()->create([
        'name' => 'Near Workplace',
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'is_active' => true,
    ]);

    $far = Workplace::factory()->create([
        'name' => 'Far Workplace',
        'latitude' => 41.0000,
        'longitude' => -75.0000,
        'is_active' => true,
    ]);

    $response = $this->withToken($this->token)->getJson('/api/workplaces?latitude=40.7128&longitude=-74.0060');

    $response->assertSuccessful();

    $workplaces = $response->json('data');
    expect($workplaces[0]['name'])->toBe('Near Workplace');
    expect($workplaces[1]['name'])->toBe('Far Workplace');
    expect($workplaces[0]['distance'])->toBeLessThan($workplaces[1]['distance']);
});

it('returns validation error for invalid latitude', function () {
    $response = $this->withToken($this->token)->getJson('/api/workplaces?latitude=invalid&longitude=-74.0060');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);
});

it('returns validation error for invalid longitude', function () {
    $response = $this->withToken($this->token)->getJson('/api/workplaces?latitude=40.7128&longitude=invalid');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);
});

it('returns workplaces without distance when no location provided', function () {
    Workplace::factory()->count(2)->create(['is_active' => true]);

    $response = $this->withToken($this->token)->getJson('/api/workplaces');

    $response->assertSuccessful();

    // Distance should not be present when no location provided
    expect($response->json('data.0'))->not->toHaveKey('distance');
});

it('requires authentication to list workplaces', function () {
    $response = $this->getJson('/api/workplaces');

    $response->assertUnauthorized();
});

it('returns empty array when no active workplaces exist', function () {
    Workplace::factory()->count(2)->create(['is_active' => false]);

    $response = $this->withToken($this->token)->getJson('/api/workplaces');

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data');
});
