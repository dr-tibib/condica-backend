<?php

declare(strict_types=1);

namespace App\Core\Entities;

use DateTimeInterface;

class WorkShift
{
    private DateTimeInterface $startTime;
    private ?DateTimeInterface $endTime;

    public function __construct(DateTimeInterface $startTime, ?DateTimeInterface $endTime = null)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * logic: isOvernight(): Returns true if the system clock detects a transition past 00:00 without a checkout.
     */
    public function isOvernight(DateTimeInterface $now): bool
    {
        if ($this->endTime !== null) {
            return false;
        }

        return $this->startTime->format('Y-m-d') !== $now->format('Y-m-d');
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
