<?php

declare(strict_types=1);

namespace App\Core\UseCases\DTOs;

class NormalFlowOutput
{
    public ?Requirement $requirement;
    public ?RefinementList $refinementList;
    public bool $shiftStarted;
    public bool $shiftEnded;
    public bool $leaveSplit;

    public function __construct(
        ?Requirement $requirement = null,
        ?RefinementList $refinementList = null,
        bool $shiftStarted = false,
        bool $shiftEnded = false,
        bool $leaveSplit = false
    ) {
        $this->requirement = $requirement;
        $this->refinementList = $refinementList;
        $this->shiftStarted = $shiftStarted;
        $this->shiftEnded = $shiftEnded;
        $this->leaveSplit = $leaveSplit;
    }
}
