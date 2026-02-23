<?php

use App\Models\Device;
use App\Models\User;
use App\Models\Employee;
use App\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create(['id' => 'test']);
    $this->domain = $this->tenant->domains()->first()?->domain ?? $this->tenant->domains()->create(['domain' => 'test.localhost'])->domain;

    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    $this->token = $this->user->createToken('Test Device')->plainTextToken;
});

it('can register a new device', function () {
    $response = $this->withToken($this->token)->postJson(getUrl('/api/devices/register'), [
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
    expect(Device::first()->employee_id)->toBe($this->employee->id);
    expect(Device::first()->platform)->toBe('ios');
});

it('updates existing device if token already exists', function () {
    Device::factory()->create([
        'employee_id' => $this->employee->id,
        'device_token' => 'same-token',
        'device_name' => 'Old Name',
        'platform' => 'ios',
    ]);

    $response = $this->withToken($this->token)->postJson(getUrl('/api/devices/register'), [
        'device_token' => 'same-token',
        'device_name' => 'New Name',
        'platform' => 'ios',
        'app_version' => '2.0.0',
    ]);

    $response->assertCreated();

    expect(Device::count())->toBe(1);
    expect(Device::first()->device_name)->toBe('New Name');
    expect(Device::first()->app_version)->toBe('2.0.0');
});

it('updates last_active_at when registering device', function () {
    $pastTime = now()->subHour();

    Device::factory()->create([
        'employee_id' => $this->employee->id,
        'device_token' => 'test-token',
        'last_active_at' => $pastTime,
    ]);

    $this->withToken($this->token)->postJson(getUrl('/api/devices/register'), [
        'device_token' => 'test-token',
        'platform' => 'ios',
    ]);

    $device = Device::first();
    expect($device->last_active_at->greaterThan($pastTime))->toBeTrue();
});

it('requires authentication to register device', function () {
    $response = $this->postJson(getUrl('/api/devices/register'), [
        'device_token' => 'test-token',
        'platform' => 'ios',
    ]);

    $response->assertUnauthorized();
});

it('allows same token for different users', function () {
    $anotherUser = User::factory()->create();
    $anotherEmployee = Employee::factory()->create(['user_id' => $anotherUser->id]);
    $anotherToken = $anotherUser->createToken('Test')->plainTextToken;

    $response1 = $this->withToken($this->token)->postJson(getUrl('/api/devices/register'), [
        'device_token' => 'shared-token',
        'platform' => 'ios',
    ]);
    $response1->assertCreated();

    Illuminate\Support\Facades\Auth::forgetGuards();

    $response2 = $this->withToken($anotherToken)->postJson(getUrl('/api/devices/register'), [
        'device_token' => 'shared-token',
        'platform' => 'android',
    ]);
    $response2->assertCreated();

    expect(Device::count())->toBe(2);
    expect(Device::where('employee_id', $this->employee->id)->count())->toBe(1);
    expect(Device::where('employee_id', $anotherEmployee->id)->count())->toBe(1);
});
