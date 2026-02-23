<?php

namespace Tests\Feature\API;

use App\Models\Delegation;
use App\Models\Employee;
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
    protected Employee $employee;
    protected Workplace $workplace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'workplace_enter_code' => '123',
        ]);

        $this->workplace = Workplace::create([
            'name' => 'HQ',
            'is_active' => true,
        ]);

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
        $startDate = now()->subDays(2)->setHour(10)->setMinute(0);

        PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'presence',
            'start_at' => $startDate->copy()->subMinute(),
            'start_method' => 'kiosk',
        ]);

        $delegationEvent = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'delegation',
            'start_at' => $startDate,
            'start_method' => 'kiosk',
            'notes' => 'Long Delegation',
        ]);

        Delegation::create([
             'employee_id' => $this->employee->id,
             'name' => 'Far Away',
             'presence_event_id' => $delegationEvent->id,
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
        $this->assertCount(3, $data['schedule_days']);
        $this->assertEquals($startDate->format('Y-m-d'), $data['schedule_days'][0]['date']);
        $this->assertEquals(now()->format('Y-m-d'), $data['schedule_days'][2]['date']);

        $this->assertEquals('09:00', $data['shift_settings']['start']);
    }

    public function test_end_delegation_with_schedule()
    {
        $startDate = now()->subDays(2)->setHour(10)->setMinute(0);
        $endDate = now()->setHour(14)->setMinute(0);

        $checkIn = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'presence',
            'start_at' => $startDate->copy()->subMinute(),
            'start_method' => 'kiosk',
        ]);

        $delegationEvent = PresenceEvent::create([
            'employee_id' => $this->employee->id,
            'workplace_id' => $this->workplace->id,
            'type' => 'delegation',
            'start_at' => $startDate,
            'start_method' => 'kiosk',
        ]);

        Delegation::create([
             'employee_id' => $this->employee->id,
             'name' => 'Far Away',
             'presence_event_id' => $delegationEvent->id,
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
            'employee_id' => $this->employee->id,
            'code' => '123',
            'schedule' => $schedule,
        ]);

        $response->assertStatus(200);

        $day1End = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'delegation')
            ->whereDate('start_at', $startDate)
            ->first();
        $this->assertNotNull($day1End);
        $this->assertEquals('18:00:00', $day1End->end_at->format('H:i:s'));

        $day1Presence = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'presence')
            ->whereDate('start_at', $startDate)
            ->first();
        $this->assertNotNull($day1Presence);
        $this->assertNotNull($day1Presence->end_at);

        $day2Delegation = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'delegation')
            ->whereDate('start_at', $startDate->copy()->addDay())
            ->first();
        $this->assertNotNull($day2Delegation);
        $this->assertEquals('09:00:00', $day2Delegation->start_at->format('H:i:s'));

        $day3Delegation = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'delegation')
            ->whereDate('start_at', $endDate)
            ->first();
        $this->assertNotNull($day3Delegation);
        $this->assertEquals('14:00:00', $day3Delegation->end_at->format('H:i:s'));

        $day3Presence = PresenceEvent::where('employee_id', $this->employee->id)
            ->where('type', 'presence')
            ->whereDate('start_at', $endDate)
            ->first();
        $this->assertNotNull($day3Presence);
        $this->assertNull($day3Presence->end_at);
    }
}
