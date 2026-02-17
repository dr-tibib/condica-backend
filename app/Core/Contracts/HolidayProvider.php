<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use DateTimeInterface;

interface HolidayProvider
{
    public function isHoliday(DateTimeInterface $date): bool;
}
