<?php

declare(strict_types=1);

namespace App\Core\Entities;

use App\Core\Contracts\HolidayProvider;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;

class Delegation
{
    private DateTimeInterface $startTime;
    private ?DateTimeInterface $endTime;

    public function __construct(DateTimeInterface $startTime, ?DateTimeInterface $endTime = null)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * logic: isMultiDay(): True if start_date and end_date differ.
     */
    public function isMultiDay(DateTimeInterface $now): bool
    {
        $comparisonTime = $this->endTime ?? $now;
        return $this->startTime->format('Y-m-d') !== $comparisonTime->format('Y-m-d');
    }

    /**
     * logic: isCancellable(): True if duration < 10 mins.
     */
    public function isCancellable(DateTimeInterface $now): bool
    {
        $comparisonTime = $this->endTime ?? $now;

        // Calculate difference in minutes
        $diff = $this->startTime->diff($comparisonTime);
        $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

        return $minutes < 10;
    }

    /**
     * logic: generateRefinementTimeline(): Generates a list of days between start and end, excluding weekends and holidays.
     *
     * @return array<int, array{date: string, start_time: string, end_time: string}>
     */
    public function generateRefinementTimeline(
        HolidayProvider $holidayProvider,
        string $defaultStart,
        string $defaultEnd,
        ?DateTimeInterface $until = null
    ): array {
        $end = $this->endTime ?? $until;

        if ($end === null) {
            return [];
        }

        $timeline = [];
        $start = DateTimeImmutable::createFromInterface($this->startTime)->setTime(0, 0);
        $endDate = DateTimeImmutable::createFromInterface($end)->setTime(0, 0)->modify('+1 day');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $endDate);

        foreach ($period as $date) {
            /** @var DateTimeInterface $date */
            // Check weekend (Sat=6, Sun=7)
            if ($date->format('N') >= 6) {
                continue;
            }

            // Check holiday
            if ($holidayProvider->isHoliday($date)) {
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

    public function getStartTime(): DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?DateTimeInterface
    {
        return $this->endTime;
    }
}
