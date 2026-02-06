<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\PresenceEvent;
use App\Models\User;
use App\Models\Workplace;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TenantTestCase;

class DelegationScheduleTest extends TenantTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workplace $workplace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'workplace_enter_code' => '123',
        ]);

        $this->workplace = Workplace::create([
            'name' => 'HQ',
            'is_active' => true,
        ]);

        // Ensure settings are seeded or set
        Setting::unguard();
        Setting::updateOrCreate(['key' => 'shift_start'], [
            'value' => '09:00',
            'name' => 'Start',
            'active' => 1,
            'field' => json_encode(['name' => 'value', 'type' => 'time'])
        ]);
        Setting::updateOrCreate(['key' => 'shift_end'], [
            'value' => '17:00',
            'name' => 'End',
            'active' => 1,
            'field' => json_encode(['name' => 'value', 'type' => 'time'])
        ]);
        Setting::reguard();
    }

    public function test_long_delegation_requires_schedule()
    {
        $startDate = now()->subDays(2)->setHour(10)->setMinute(0); // 2 days ago at 10:00

        // Create initial check-in and delegation
        $checkIn = PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'check_in',
            'event_time' => $startDate->copy()->subMinute(),
            'method' => 'kiosk',
        ]);

        $delegationStart = PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => $startDate,
            'method' => 'kiosk',
            'notes' => 'Long Delegation',
        ]);

        Delegation::create([
             'user_id' => $this->user->id,
             'name' => 'Far Away',
             'start_event_id' => $delegationStart->id,
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/kiosk/submit-code";

        $response = $this->postJson($url, [
            'code' => '123',
            'flow' => 'delegation',
            'workplace_id' => $this->workplace->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['type' => 'delegation_end_schedule_required']);

        $data = $response->json();
        $this->assertCount(3, $data['schedule_days']); // Day 1, Day 2, Day 3 (Today)
        $this->assertEquals($startDate->format('Y-m-d'), $data['schedule_days'][0]['date']);
        $this->assertEquals(now()->format('Y-m-d'), $data['schedule_days'][2]['date']);

        $this->assertEquals('09:00', $data['shift_settings']['start']);
    }

    public function test_end_delegation_with_schedule()
    {
        $startDate = now()->subDays(2)->setHour(10)->setMinute(0); // 2 days ago
        $endDate = now()->setHour(14)->setMinute(0); // Today

        // Create delegation
        $checkIn = PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'check_in',
            'event_time' => $startDate->copy()->subMinute(),
            'method' => 'kiosk',
        ]);

        $delegationStart = PresenceEvent::create([
            'user_id' => $this->user->id,
            'workplace_id' => $this->workplace->id,
            'event_type' => 'delegation_start',
            'event_time' => $startDate,
            'method' => 'kiosk',
        ]);

        Delegation::create([
             'user_id' => $this->user->id,
             'name' => 'Far Away',
             'start_event_id' => $delegationStart->id,
        ]);

        $schedule = [
            [
                'date' => $startDate->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '18:00',
            ],
            [
                'date' => $startDate->copy()->addDay()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
            [
                'date' => $endDate->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '14:00',
            ]
        ];

        $domain = $this->tenant->domains->first()->domain;
        $url = "http://{$domain}/api/kiosk/end-delegation-schedule";

        $response = $this->postJson($url, [
            'user_id' => $this->user->id,
            'code' => '123',
            'schedule' => $schedule,
        ]);

        $response->assertStatus(200);

        // Verify Day 1
        $day1End = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'delegation_end')
            ->whereDate('event_time', $startDate)
            ->first();
        $this->assertNotNull($day1End);
        $this->assertEquals('18:00:00', $day1End->event_time->format('H:i:s'));

        $day1CheckOut = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'check_out')
            ->whereDate('event_time', $startDate)
            ->first();
        $this->assertNotNull($day1CheckOut);

        // Verify Day 2
        $day2Start = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'delegation_start')
            ->whereDate('event_time', $startDate->copy()->addDay())
            ->first();
        $this->assertNotNull($day2Start);
        $this->assertEquals('09:00:00', $day2Start->event_time->format('H:i:s'));

        // Verify Day 3 (Last Day)
        $day3End = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'delegation_end')
            ->whereDate('event_time', $endDate)
            ->first();
        $this->assertNotNull($day3End);
        $this->assertEquals('14:00:00', $day3End->event_time->format('H:i:s'));

        // Ensure NO checkout on Day 3
        $day3CheckOut = PresenceEvent::where('user_id', $this->user->id)
            ->where('event_type', 'check_out')
            ->whereDate('event_time', $endDate)
            ->first();
        $this->assertNull($day3CheckOut);
    }
}
