<?php

declare(strict_types=1);

namespace App\Core\Entities;

use App\Core\Contracts\HolidayProvider;
use DateTimeImmutable;
use DateTimeInterface;

class Leave
{
    private DateTimeInterface $startDate;
    private DateTimeInterface $endDate;
    private ?int $id;

    public function __construct(DateTimeInterface $startDate, DateTimeInterface $endDate, ?int $id = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->id = $id;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function calculateEndDate(
        DateTimeInterface $startDate,
        int $days,
        HolidayProvider $holidayProvider
    ): DateTimeInterface {
        $currentDate = DateTimeImmutable::createFromInterface($startDate);
        $daysFound = 0;

        if ($days <= 0) {
            return $currentDate;
        }

        while ($daysFound < $days) {
            $isWeekend = (int) $currentDate->format('N') >= 6;
            $isHoliday = $holidayProvider->isHoliday($currentDate);

            if (! $isWeekend && ! $isHoliday) {
                $daysFound++;
            }

            if ($daysFound === $days) {
                break;
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $currentDate;
    }
}
