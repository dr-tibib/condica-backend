<?php

declare(strict_types=1);

namespace App\Core\Contracts\Repositories;

use App\Core\Entities\Leave;
use DateTimeInterface;

interface ILeaveRepository
{
    public function findActiveLeave(int $employeeId, DateTimeInterface $date): ?Leave;
    public function saveLeave(int $employeeId, Leave $leave): void;
    public function deleteLeave(int $employeeId, Leave $leave): void;
}
