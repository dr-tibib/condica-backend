<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class DashboardNoEmployeeTest extends TenantTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fix for Backpack guard issue in tests
        config(['backpack.base.guard' => 'web']);
    }

    public function test_dashboard_shows_error_when_no_employee_profile()
    {
        // 1. Create User without Employee profile
        $user = User::factory()->create();

        // 2. Authenticate and Visit
        $response = $this->actingAs($user)
            ->get(route('backpack.dashboard'));

        // 3. Assertions
        $response->assertStatus(200);
        $response->assertViewIs('admin.errors.no_employee_profile');
        $response->assertSee('No Employee Profile Found!');
    }
}
