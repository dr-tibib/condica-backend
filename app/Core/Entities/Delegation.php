<?php

declare(strict_types=1);

namespace App\Core\Entities;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Delegation
{
    private Carbon $startTime;
    private ?Carbon $endTime;

    public function __construct(Carbon $startTime, ?Carbon $endTime = null)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * logic: isMultiDay(): True if start_date and end_date differ.
     */
    public function isMultiDay(?Carbon $now = null): bool
    {
        $comparisonTime = $this->endTime ?? $now ?? Carbon::now();
        return ! $this->startTime->isSameDay($comparisonTime);
    }

    /**
     * logic: isCancellable(): True if duration < 10 mins.
     */
    public function isCancellable(?Carbon $now = null): bool
    {
        $comparisonTime = $this->endTime ?? $now ?? Carbon::now();
        return $this->startTime->diffInMinutes($comparisonTime) < 10;
    }

    /**
     * logic: generateRefinementTimeline(): Generates a list of days between start and end, excluding weekends ( and ), pre-filled with Workplace default hours.
     *
     * @return array<int, array{date: string, start_time: string, end_time: string}>
     */
    public function generateRefinementTimeline(string $defaultStart, string $defaultEnd, ?Carbon $until = null): array
    {
        $timeline = [];
        $end = $this->endTime ?? $until ?? Carbon::now();

        // Ensure we cover the full range of days.
        $period = CarbonPeriod::create($this->startTime, '1 day', $end);

        foreach ($period as $date) {
            /** @var Carbon $date */
            if ($date->isWeekend()) {
                continue;
            }

            $timeline[] = [
                'date' => $date->format('Y-m-d'),
                'start_time' => $defaultStart,
                'end_time' => $defaultEnd,
            ];
        }

        return $timeline;
    }

    public function getStartTime(): Carbon
    {
        return $this->startTime;
    }

    public function getEndTime(): ?Carbon
    {
        return $this->endTime;
    }
}
