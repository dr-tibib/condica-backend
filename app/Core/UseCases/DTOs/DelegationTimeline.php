<?php

declare(strict_types=1);

namespace App\Core\UseCases\DTOs;

class DelegationTimeline
{
    /** @var array<array{date: string, start_time: string, end_time: string}> */
    public array $days;

    public function __construct(array $days)
    {
        $this->days = $days;
    }
}
