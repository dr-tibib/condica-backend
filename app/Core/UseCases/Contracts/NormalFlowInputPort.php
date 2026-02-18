<?php

declare(strict_types=1);

namespace App\Core\UseCases\Contracts;

use App\Core\UseCases\DTOs\NormalFlowOutput;
use DateTimeInterface;

interface NormalFlowInputPort
{
    public function execute(string $workplaceEnterCode, DateTimeInterface $currentTime): NormalFlowOutput;
}
