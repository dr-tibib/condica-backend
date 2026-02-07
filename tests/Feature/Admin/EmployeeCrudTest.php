<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TenantTestCase;

class EmployeeCrudTest extends TenantTestCase
{
    public function test_it_can_list_employees()
    {
        $user = User::factory()->create([
            'is_global_superadmin' => true,
        ]);

        $response = $this->actingAs($user, 'backpack')
                         ->get(backpack_url('employee'));

        $response->assertStatus(200);
    }

    public function test_it_can_create_employee()
    {
        $user = User::factory()->create([
             'is_global_superadmin' => true,
        ]);

        $response = $this->actingAs($user, 'backpack')
                         ->post(backpack_url('employee'), [
                             'first_name' => 'John',
                             'last_name' => 'Doe',
                             'email' => 'john.doe@example.com',
                             'phone' => '1234567890',
                         ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('employees', [
            'email' => 'john.doe@example.com',
        ]);
    }
}
