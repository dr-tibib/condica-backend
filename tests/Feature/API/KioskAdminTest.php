<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TenantTestCase;

class KioskAdminTest extends TenantTestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin Role
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        Workplace::create(['name' => 'Office A', 'is_active' => true]);
        Workplace::create(['name' => 'Office B', 'is_active' => true]);
    }

    public function test_admin_can_login_and_has_token()
    {
        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/login";

        $response = $this->postJson($url, [
            'email' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_admin_can_fetch_workplaces()
    {
        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/workplaces";

        // Login first to get token
        $loginResponse = $this->postJson("http://{$domain}/api/login", [
            'email' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);
        $token = $loginResponse->json('token');

        $response = $this->withToken($token)->getJson($url);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_regular_user_can_login_but_setup_will_be_blocked_by_frontend_check_or_backend_check()
    {
        // Login works for everyone (generic login)
        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/login";

        $response = $this->postJson($url, [
            'email' => 'user@example.com',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200);

        // We will assert later that this user DOES NOT have the admin role in the response
        // if we decide to include roles in login response.
    }
}
