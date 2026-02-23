<?php

use App\Models\User;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create(['id' => 'test']);
    $this->domain = $this->tenant->domains()->first()?->domain ?? $this->tenant->domains()->create(['domain' => 'test.localhost'])->domain;

    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
});


it('can login with valid credentials', function () {
    $response = $this->postJson(getUrl('/api/login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);

    expect($response->json('user.email'))->toBe('test@example.com');
    expect($response->json('token'))->toBeString();
});

it('returns validation error when email is missing', function () {
    $response = $this->postJson(getUrl('/api/login'), [
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('can logout successfully', function () {
    $token = $this->user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->postJson(getUrl('/api/logout'));

    $response->assertSuccessful()
        ->assertJson(['message' => 'Successfully logged out.']);

    expect($this->user->tokens()->count())->toBe(0);
});

it('can get authenticated user', function () {
    $token = $this->user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->getJson(getUrl('/api/user'));

    $response->assertSuccessful()
        ->assertJson([
            'id' => $this->user->id,
            'email' => $this->user->email,
        ]);
});
