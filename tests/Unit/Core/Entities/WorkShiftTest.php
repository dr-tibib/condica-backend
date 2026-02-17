<?php

use App\Core\Entities\WorkShift;
use Carbon\Carbon;

test('isOvernight returns true when shift spans midnight', function () {
    $startTime = Carbon::create(2023, 10, 26, 23, 0, 0);
    $workShift = new WorkShift($startTime);

    $now = Carbon::create(2023, 10, 27, 0, 1, 0); // Next day

    expect($workShift->isOvernight($now))->toBeTrue();
});

test('isOvernight returns false when shift is on same day', function () {
    $startTime = Carbon::create(2023, 10, 26, 8, 0, 0);
    $workShift = new WorkShift($startTime);

    $now = Carbon::create(2023, 10, 26, 16, 0, 0); // Same day

    expect($workShift->isOvernight($now))->toBeFalse();
});

test('isOvernight returns false if shift has ended', function () {
    $startTime = Carbon::create(2023, 10, 26, 23, 0, 0);
    $endTime = Carbon::create(2023, 10, 27, 1, 0, 0); // Ended next day
    $workShift = new WorkShift($startTime, $endTime);

    // Even if we check "now" which is later, it should return false because it's not an active overnight shift without checkout
    expect($workShift->isOvernight())->toBeFalse();
});

test('isOvernight uses current time if not provided', function () {
    Carbon::setTestNow(Carbon::create(2023, 10, 27, 1, 0, 0));
    $startTime = Carbon::create(2023, 10, 26, 23, 0, 0);
    $workShift = new WorkShift($startTime);

    expect($workShift->isOvernight())->toBeTrue();
});
