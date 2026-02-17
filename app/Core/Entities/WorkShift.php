<?php

declare(strict_types=1);

namespace App\Core\Entities;

use Carbon\Carbon;

class WorkShift
{
    private Carbon $startTime;
    private ?Carbon $endTime;

    public function __construct(Carbon $startTime, ?Carbon $endTime = null)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * logic: isOvernight(): Returns true if the system clock detects a transition past 00:00 without a checkout.
     */
    public function isOvernight(?Carbon $now = null): bool
    {
        if ($this->endTime !== null) {
            return false;
        }

        $now = $now ?? Carbon::now();

        return ! $this->startTime->isSameDay($now);
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
