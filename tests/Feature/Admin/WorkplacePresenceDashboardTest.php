<?php

namespace Tests\Feature\Admin;

use App\Models\PresenceEvent;
use App\Models\User;
use App\Models\Workplace;
use Carbon\Carbon;
use Tests\TenantTestCase;

class WorkplacePresenceDashboardTest extends TenantTestCase
{
    public function test_dashboard_loads_with_default_data()
    {
        // Config Backpack to use web guard for tests if backpack guard is missing
        config(['backpack.base.guard' => 'web']);

        // 1. Create User/Admin
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        \App\Models\Employee::factory()->create(['user_id' => $admin->id]);

        // 2. Create Workplace
        $workplace = Workplace::factory()->create();

        // 3. Create Presence Events (Today)
        // User 1: Checked In 1 hour ago
        $user1 = User::factory()->create(['name' => 'User One']);
        $emp1 = \App\Models\Employee::factory()->create(['user_id' => $user1->id, 'first_name' => 'User One']);

        PresenceEvent::factory()->create([
            'employee_id' => $emp1->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subHour(),
        ]);

        // User 2: Checked In 2 hours ago, Checked Out 1 hour ago
        $user2 = User::factory()->create(['name' => 'User Two']);
        $emp2 = \App\Models\Employee::factory()->create(['user_id' => $user2->id, 'first_name' => 'User Two']);

        $in = PresenceEvent::factory()->create([
            'employee_id' => $emp2->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_in',
            'event_time' => now()->subHours(2),
        ]);
        PresenceEvent::factory()->create([
            'employee_id' => $emp2->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_out',
            'event_time' => now()->subHour(),
            'pair_event_id' => $in->id,
        ]);
        $in->update(['pair_event_id' => PresenceEvent::latest()->first()->id]);

        // 4. Authenticate
        $this->actingAs($admin);

        // 5. Visit Dashboard (HTML)
        $response = $this->get('admin/workplace-presence');

        $response->assertStatus(200);
        $response->assertSee('Employees Currently Working');
        // Widgets are in HTML, but Table data is AJAX.

        // 6. Verify Data via Search (AJAX)
        $searchResponse = $this->post('admin/workplace-presence/search');

        $searchResponse->assertStatus(200);

        $content = $searchResponse->getContent();
        $this->assertStringContainsString('User One', $content);
        $this->assertStringContainsString('User Two', $content);
        $this->assertStringContainsString('Working Now', $content); // User 1 status
    }

    public function test_dashboard_loads_with_date_filter()
    {
        config(['backpack.base.guard' => 'web']);

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        \App\Models\Employee::factory()->create(['user_id' => $admin->id]);

        $workplace = Workplace::factory()->create();

        // User 3: Worked Yesterday
        $user3 = User::factory()->create(['name' => 'User Three']);
        $emp3 = \App\Models\Employee::factory()->create(['user_id' => $user3->id, 'first_name' => 'User Three']);

        $yesterday = now()->subDay();

        $in = PresenceEvent::factory()->create([
            'employee_id' => $emp3->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_in',
            'event_time' => $yesterday->copy()->setHour(9),
        ]);
        PresenceEvent::factory()->create([
            'employee_id' => $emp3->id,
            'workplace_id' => $workplace->id,
            'event_type' => 'check_out',
            'event_time' => $yesterday->copy()->setHour(17),
            'pair_event_id' => $in->id,
        ]);
        $in->update(['pair_event_id' => PresenceEvent::latest()->first()->id]);

        $this->actingAs($admin);

        // Filter for Yesterday
        $filter = json_encode([
            'from' => $yesterday->format('Y-m-d'),
            'to' => $yesterday->format('Y-m-d')
        ]);

        // HTML check
        $response = $this->get('admin/workplace-presence?date_range=' . urlencode($filter));
        $response->assertStatus(200);
        // Widgets should reflect filtered count?
        // My controller logic for "Active in Range" widget relies on the request param.
        // So it should be in HTML.

        // AJAX Search with Filter
        $searchResponse = $this->post('admin/workplace-presence/search?date_range=' . urlencode($filter));
        $searchResponse->assertStatus(200);

        $content = $searchResponse->getContent();
        $this->assertStringContainsString('User Three', $content);

        // Check formatting: "09:00" and "17:00"
        $this->assertStringContainsString($yesterday->copy()->setHour(9)->format('H:i'), $content);
        $this->assertStringContainsString($yesterday->copy()->setHour(17)->format('H:i'), $content);

        // Should NOT see "Working Now" for User 3
        $this->assertStringNotContainsString('Working Now', $content);
        // Should see "Present" badge
        $this->assertStringContainsString('Present', $content);
    }
}
