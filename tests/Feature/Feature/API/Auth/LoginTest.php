<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
});

it('can login with valid credentials', function () {
    $response = $this->postJson('/api/login', [
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
    $response = $this->postJson('/api/login', [
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns validation error when password is missing', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('returns validation error when device name is missing', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['device_name']);
});

it('returns error with invalid email format', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'invalid-email',
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns error with incorrect password', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns error when user does not exist', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('can logout successfully', function () {
    $token = $this->user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/logout');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Successfully logged out.']);

    // Verify token is revoked
    expect($this->user->tokens()->count())->toBe(0);
});

it('requires authentication to logout', function () {
    $response = $this->postJson('/api/logout');

    $response->assertUnauthorized();
});

it('can get authenticated user', function () {
    $token = $this->user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/user');

    $response->assertSuccessful()
        ->assertJson([
            'id' => $this->user->id,
            'email' => $this->user->email,
        ]);
});

it('requires authentication to get user', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});
