<?php

use App\Models\Device;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('Test Device')->plainTextToken;
});

it('can register a new device', function () {
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'unique-device-token-123',
        'device_name' => 'iPhone 14 Pro',
        'platform' => 'ios',
        'app_version' => '1.0.0',
        'os_version' => '17.0',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Device registered successfully.',
        ])
        ->assertJsonStructure([
            'device' => ['id', 'device_token', 'device_name', 'platform'],
        ]);

    expect(Device::count())->toBe(1);
    expect(Device::first()->user_id)->toBe($this->user->id);
    expect(Device::first()->platform)->toBe('ios');
});

it('updates existing device if token already exists', function () {
    // Register device first time
    Device::factory()->create([
        'user_id' => $this->user->id,
        'device_token' => 'same-token',
        'device_name' => 'Old Name',
        'platform' => 'ios',
    ]);

    // Register again with same token
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'same-token',
        'device_name' => 'New Name',
        'platform' => 'ios',
        'app_version' => '2.0.0',
    ]);

    $response->assertCreated();

    expect(Device::count())->toBe(1); // Should still be 1
    expect(Device::first()->device_name)->toBe('New Name');
    expect(Device::first()->app_version)->toBe('2.0.0');
});

it('updates last_active_at when registering device', function () {
    $pastTime = now()->subHour();

    Device::factory()->create([
        'user_id' => $this->user->id,
        'device_token' => 'test-token',
        'last_active_at' => $pastTime,
    ]);

    $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'test-token',
        'platform' => 'ios',
    ]);

    $device = Device::first();
    expect($device->last_active_at->greaterThan($pastTime))->toBeTrue();
});

it('returns validation error when device_token is missing', function () {
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'platform' => 'ios',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['device_token']);
});

it('returns validation error when platform is missing', function () {
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'test-token',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['platform']);
});

it('returns validation error for invalid platform', function () {
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'test-token',
        'platform' => 'windows', // Only ios and android allowed
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['platform']);
});

it('can register android device', function () {
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'android-token',
        'device_name' => 'Samsung Galaxy S23',
        'platform' => 'android',
        'app_version' => '1.0.0',
        'os_version' => '14',
    ]);

    $response->assertCreated();

    expect(Device::first()->platform)->toBe('android');
});

it('can register device without optional fields', function () {
    $response = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'minimal-token',
        'platform' => 'ios',
    ]);

    $response->assertCreated();

    $device = Device::first();
    expect($device->device_name)->toBeNull();
    expect($device->app_version)->toBeNull();
    expect($device->os_version)->toBeNull();
});

it('requires authentication to register device', function () {
    $response = $this->postJson('/api/devices/register', [
        'device_token' => 'test-token',
        'platform' => 'ios',
    ]);

    $response->assertUnauthorized();
});

it('allows same token for different users', function () {
    $anotherUser = User::factory()->create();
    $anotherToken = $anotherUser->createToken('Test')->plainTextToken;

    // User 1 registers device
    $response1 = $this->withToken($this->token)->postJson('/api/devices/register', [
        'device_token' => 'shared-token',
        'platform' => 'ios',
    ]);
    $response1->assertCreated();

    // User 2 registers device with same token
    $response2 = $this->withToken($anotherToken)->postJson('/api/devices/register', [
        'device_token' => 'shared-token',
        'platform' => 'android',
    ]);
    $response2->assertCreated();

    expect(Device::count())->toBe(2);
    expect(Device::where('user_id', $this->user->id)->count())->toBe(1);
    expect(Device::where('user_id', $anotherUser->id)->count())->toBe(1);
});
