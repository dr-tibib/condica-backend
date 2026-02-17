<?php

declare(strict_types=1);

namespace App\Core\Entities;

use App\Core\Contracts\HolidayProvider;
use DateTimeImmutable;
use DateTimeInterface;

class Leave
{
    public function calculateEndDate(
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
