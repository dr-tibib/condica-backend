<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Workplace;
use Carbon\Carbon;
use Tests\TenantTestCase;
use Spatie\Permission\Models\Role;

class WorkplacePresenceDashboardTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['backpack.base.guard' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_dashboard_loads_with_default_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $admin->id]);

        $wp = Workplace::factory()->create(['name' => 'Main Office']);
        
        $user1 = User::factory()->create();
        $emp1 = Employee::factory()->create(['user_id' => $user1->id, 'first_name' => 'User One']);
        
        PresenceEvent::create([
            'employee_id' => $emp1->id,
            'workplace_id' => $wp->id,
            'type' => 'presence',
            'start_at' => now()->subHour(),
            'start_method' => 'manual',
        ]);

        $user2 = User::factory()->create();
        $emp2 = Employee::factory()->create(['user_id' => $user2->id, 'first_name' => 'User Two']);
        
        PresenceEvent::create([
            'employee_id' => $emp2->id,
            'workplace_id' => $wp->id,
            'type' => 'presence',
            'start_at' => now()->subHours(3),
            'end_at' => now()->subHours(1),
            'start_method' => 'manual',
            'end_method' => 'manual',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/admin/workplace-presence";
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertSee('User One');
        $response->assertSee('User Two');
        $response->assertSee('Working Now');
        $response->assertSee('Present');
    }

    public function test_dashboard_loads_with_date_filter()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $admin->id]);

        $wp = Workplace::factory()->create();
        $user3 = User::factory()->create();
        $emp3 = Employee::factory()->create(['user_id' => $user3->id, 'first_name' => 'User Three']);

        $yesterday = now()->subDay();
        PresenceEvent::create([
            'employee_id' => $emp3->id,
            'workplace_id' => $wp->id,
            'type' => 'presence',
            'start_at' => $yesterday->copy()->setHour(9),
            'end_at' => $yesterday->copy()->setHour(17),
            'start_method' => 'manual',
            'end_method' => 'manual',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $dateRange = json_encode(['from' => $yesterday->toDateString(), 'to' => $yesterday->toDateString()]);
        $url = "http://{$domain}/admin/workplace-presence?date_range=" . urlencode($dateRange);
        
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertSee('User Three');
        $response->assertSee('8h 00m');
    }
}
