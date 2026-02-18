<?php

declare(strict_types=1);

namespace App\Core\UseCases\Contracts;

use App\Core\UseCases\DTOs\DelegationFlowOutput;
use DateTimeInterface;

interface DelegationFlowInputPort
{
    public function start(string $workplaceEnterCode, int $vehicleId, array $locations, DateTimeInterface $currentTime): DelegationFlowOutput;

    public function end(string $workplaceEnterCode, DateTimeInterface $currentTime): DelegationFlowOutput;
}
