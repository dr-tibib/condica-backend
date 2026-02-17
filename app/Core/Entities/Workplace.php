<?php

declare(strict_types=1);

namespace App\Core\Entities;

class Workplace
{
    private string $defaultShiftStart;
    private string $defaultShiftEnd;
    private string $lateStartThreshold;

    public function __construct(string $defaultShiftStart, string $defaultShiftEnd, string $lateStartThreshold = '16:00')
    {
        $this->defaultShiftStart = $defaultShiftStart;
        $this->defaultShiftEnd = $defaultShiftEnd;
        $this->lateStartThreshold = $lateStartThreshold;
    }

    public function getDefaultShiftStart(): string
    {
        return $this->defaultShiftStart;
    }

    public function getDefaultShiftEnd(): string
    {
        return $this->defaultShiftEnd;
    }

    public function getLateStartThreshold(): string
    {
        return $this->lateStartThreshold;
    }
}
