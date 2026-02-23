<?php

namespace Tests\Unit\Models;

use App\Models\PresenceEvent;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PresenceEventTest extends TestCase
{
    public function test_is_overnight_returns_true_when_shift_spans_midnight()
    {
        $startTime = Carbon::now()->subDay()->setHour(22); // Yesterday at 22:00
        $shift = new PresenceEvent(['start_at' => $startTime]);

        $this->assertTrue($shift->isOvernight(Carbon::now()->setHour(2))); // Today at 02:00
    }

    public function test_is_overnight_returns_false_when_shift_is_on_same_day()
    {
        $startTime = Carbon::now()->setHour(9); // Today at 09:00
        $shift = new PresenceEvent(['start_at' => $startTime]);

        $this->assertFalse($shift->isOvernight(Carbon::now()->setHour(17))); // Today at 17:00
    }

    public function test_is_overnight_returns_false_if_shift_has_ended()
    {
        $startTime = Carbon::now()->subDay()->setHour(22);
        $endTime = Carbon::now()->setHour(06);
        $shift = new PresenceEvent(['start_at' => $startTime, 'end_at' => $endTime]);

        $this->assertFalse($shift->isOvernight());
    }

    public function test_is_overnight_uses_current_time_if_not_provided()
    {
        $startTime = Carbon::now()->subDay();
        $shift = new PresenceEvent(['start_at' => $startTime]);

        $this->assertTrue($shift->isOvernight());
    }
}
