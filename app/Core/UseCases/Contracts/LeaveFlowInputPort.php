<?php

declare(strict_types=1);

namespace App\Core\UseCases\Contracts;

use App\Core\UseCases\DTOs\LeaveFlowOutput;
use DateTimeInterface;

interface LeaveFlowInputPort
{
    public function execute(
        string $workplaceEnterCode,
        DateTimeInterface $startDate,
        int $days,
        DateTimeInterface $currentTime
    ): LeaveFlowOutput;
}
