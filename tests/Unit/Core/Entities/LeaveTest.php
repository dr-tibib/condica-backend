<?php

use App\Core\Contracts\HolidayProvider;
use App\Core\Entities\Leave;
use Carbon\Carbon;

test('calculateEndDate skips weekends', function () {
    $leave = new Leave();
    $holidayProvider = Mockery::mock(HolidayProvider::class);
    $holidayProvider->shouldReceive('isHoliday')->andReturn(false);

    // Start Friday, 3 days.
    // Fri (1), Sat (skip), Sun (skip), Mon (2), Tue (3). End Tuesday.
    $startDate = Carbon::create(2023, 10, 27); // Friday

    $endDate = $leave->calculateEndDate($startDate, 3, $holidayProvider);

    expect($endDate->format('Y-m-d'))->toBe('2023-10-31');
});

test('calculateEndDate skips holidays', function () {
    $leave = new Leave();
    $holidayProvider = Mockery::mock(HolidayProvider::class);

    // Start Monday, 2 days. Tuesday is holiday.
    // Mon (1), Tue (skip), Wed (2). End Wednesday.

    $startDate = Carbon::create(2023, 10, 23); // Monday

    $holidayProvider->shouldReceive('isHoliday')
        ->andReturnUsing(function ($date) {
            return $date->format('Y-m-d') === '2023-10-24';
        });

    $endDate = $leave->calculateEndDate($startDate, 2, $holidayProvider);

    expect($endDate->format('Y-m-d'))->toBe('2023-10-25');
});

test('calculateEndDate returns start date if days is 1 and it is working day', function () {
    $leave = new Leave();
    $holidayProvider = Mockery::mock(HolidayProvider::class);
    $holidayProvider->shouldReceive('isHoliday')->andReturn(false);

    $startDate = Carbon::create(2023, 10, 23); // Monday

    $endDate = $leave->calculateEndDate($startDate, 1, $holidayProvider);

    expect($endDate->format('Y-m-d'))->toBe('2023-10-23');
});
