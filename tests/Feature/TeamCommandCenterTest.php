<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TenantTestCase;

class TeamCommandCenterTest extends TenantTestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_correctly_identify_active_employees()
    {
        $tenant = Tenant::first();
        
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'super-admin', 'guard_name' => 'backpack']);
        
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $workplace = Workplace::factory()->create();

        // 1. Employee Active
        $employee1 = Employee::factory()->create(['workplace_id' => $workplace->id]);
        PresenceEvent::create([
            'employee_id' => $employee1->id,
            'workplace_id' => $workplace->id,
            'type' => 'presence',
            'start_at' => Carbon::today()->setHour(9),
            'start_method' => 'manual',
        ]);

        // 2. Employee in Delegation
        $employee2 = Employee::factory()->create(['workplace_id' => $workplace->id]);
        PresenceEvent::create([
            'employee_id' => $employee2->id,
            'workplace_id' => $workplace->id,
            'type' => 'delegation',
            'start_at' => Carbon::today()->setHour(10),
            'start_method' => 'manual',
        ]);

        $domain = $tenant->domains->first()->domain;
        $response = $this->actingAs($user, 'backpack')->get("http://{$domain}/admin/team-command-center");

        $response->assertStatus(200);
        
        $response->assertViewHas('stats');
        $stats = $response->viewData('stats');
        
        $this->assertEquals(2, $stats['on_shift']);
        $this->assertEquals(1, $stats['on_delegation']);
    }
}
