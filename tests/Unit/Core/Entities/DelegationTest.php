<?php

use App\Core\Entities\Delegation;
use Carbon\Carbon;

test('isMultiDay returns true when delegation spans multiple days', function () {
    $startTime = Carbon::create(2023, 10, 26, 10, 0, 0);
    $delegation = new Delegation($startTime);

    $now = Carbon::create(2023, 10, 27, 10, 0, 0); // Next day

    expect($delegation->isMultiDay($now))->toBeTrue();
});

test('isMultiDay returns false when delegation is on same day', function () {
    $startTime = Carbon::create(2023, 10, 26, 8, 0, 0);
    $delegation = new Delegation($startTime);

    $now = Carbon::create(2023, 10, 26, 16, 0, 0); // Same day

    expect($delegation->isMultiDay($now))->toBeFalse();
});

test('isCancellable returns true if duration is less than 10 minutes', function () {
    $startTime = Carbon::create(2023, 10, 26, 10, 0, 0);
    $delegation = new Delegation($startTime);

    $now = Carbon::create(2023, 10, 26, 10, 9, 59); // 9m 59s

    expect($delegation->isCancellable($now))->toBeTrue();
});

test('isCancellable returns false if duration is 10 minutes or more', function () {
    $startTime = Carbon::create(2023, 10, 26, 10, 0, 0);
    $delegation = new Delegation($startTime);

    $now = Carbon::create(2023, 10, 26, 10, 10, 0); // 10m

    expect($delegation->isCancellable($now))->toBeFalse();
});

test('generateRefinementTimeline excludes weekends', function () {
    // Friday to Monday
    $startTime = Carbon::create(2023, 10, 27, 10, 0, 0); // Friday
    $endTime = Carbon::create(2023, 10, 30, 10, 0, 0); // Monday

    $delegation = new Delegation($startTime, $endTime);

    $timeline = $delegation->generateRefinementTimeline('09:00', '17:00');

    // Should include Friday (27th) and Monday (30th). Saturday (28th) and Sunday (29th) should be excluded.
    expect($timeline)->toHaveCount(2);
    expect($timeline[0]['date'])->toBe('2023-10-27');
    expect($timeline[1]['date'])->toBe('2023-10-30');
});

test('generateRefinementTimeline uses default hours', function () {
    $startTime = Carbon::create(2023, 10, 27, 10, 0, 0);
    $endTime = Carbon::create(2023, 10, 27, 12, 0, 0);

    $delegation = new Delegation($startTime, $endTime);

    $timeline = $delegation->generateRefinementTimeline('09:00', '17:00');

    expect($timeline)->toHaveCount(1);
    expect($timeline[0]['start_time'])->toBe('09:00');
    expect($timeline[0]['end_time'])->toBe('17:00');
});
