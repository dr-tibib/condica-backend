<?php

namespace Tests\Unit\Models;

use App\Models\Delegation;
use App\Models\PresenceEvent;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DelegationTest extends TestCase
{
    public function test_is_multi_day_returns_true_when_delegation_spans_multiple_days()
    {
        $start = Carbon::parse('2026-02-18 10:00:00');
        $end = Carbon::parse('2026-02-19 10:00:00');
        
        $presence = new PresenceEvent(['start_at' => $start, 'end_at' => $end]);
        $delegation = new Delegation();
        $delegation->setRelation('presenceEvent', $presence);

        $this->assertTrue($delegation->isMultiDay());
    }

    public function test_is_multi_day_returns_false_when_delegation_is_on_same_day()
    {
        $start = Carbon::parse('2026-02-18 10:00:00');
        $end = Carbon::parse('2026-02-18 18:00:00');

        $presence = new PresenceEvent(['start_at' => $start, 'end_at' => $end]);
        $delegation = new Delegation();
        $delegation->setRelation('presenceEvent', $presence);

        $this->assertFalse($delegation->isMultiDay());
    }

    public function test_is_cancellable_returns_true_if_duration_is_less_than_10_minutes()
    {
        $start = Carbon::now();
        $presence = new PresenceEvent(['start_at' => $start]);
        $delegation = new Delegation();
        $delegation->setRelation('presenceEvent', $presence);

        $this->assertTrue($delegation->isCancellable($start->copy()->addMinutes(5)));
    }

    public function test_is_cancellable_returns_false_if_duration_is_10_minutes_or_more()
    {
        $start = Carbon::now();
        $presence = new PresenceEvent(['start_at' => $start]);
        $delegation = new Delegation();
        $delegation->setRelation('presenceEvent', $presence);

        $this->assertFalse($delegation->isCancellable($start->copy()->addMinutes(10)));
    }

    public function test_generate_refinement_timeline_excludes_weekends()
    {
        // Feb 20, 2026 is Friday. Feb 23 is Monday.
        $start = Carbon::parse('2026-02-20 10:00:00');
        $end = Carbon::parse('2026-02-23 10:00:00');

        $presence = new PresenceEvent(['start_at' => $start, 'end_at' => $end]);
        $delegation = new Delegation();
        $delegation->setRelation('presenceEvent', $presence);

        $timeline = $delegation->generateRefinementTimeline('09:00', '17:00');

        // Should include Friday and Monday, but not Sat/Sun
        $this->assertCount(2, $timeline);
        $this->assertEquals('2026-02-20', $timeline[0]['date']);
        $this->assertEquals('2026-02-23', $timeline[1]['date']);
    }
}
