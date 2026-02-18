<?php

declare(strict_types=1);

namespace App\Core\UseCases\DTOs;

class LeaveFlowOutput
{
    public bool $leaveCreated;
    public ?string $message;

    public function __construct(bool $leaveCreated = false, ?string $message = null)
    {
        $this->leaveCreated = $leaveCreated;
        $this->message = $message;
    }
}
