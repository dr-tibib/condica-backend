<?php

declare(strict_types=1);

namespace App\Core\UseCases\DTOs;

class DelegationFlowOutput
{
    public bool $delegationStarted;
    public bool $delegationEnded;
    public bool $rollbackExecuted;
    public ?DelegationTimeline $timeline;

    public function __construct(
        bool $delegationStarted = false,
        bool $delegationEnded = false,
        bool $rollbackExecuted = false,
        ?DelegationTimeline $timeline = null
    ) {
        $this->delegationStarted = $delegationStarted;
        $this->delegationEnded = $delegationEnded;
        $this->rollbackExecuted = $rollbackExecuted;
        $this->timeline = $timeline;
    }
}
