<?php

declare(strict_types=1);

namespace App\Core\Contracts\Repositories;

use App\Core\Entities\Delegation;
use App\Core\Entities\WorkShift;
use DateTimeInterface;

interface IPresenceRepository
{
    public function findActiveWorkShift(int $employeeId): ?WorkShift;
    public function findLastWorkShift(int $employeeId): ?WorkShift;
    public function saveWorkShift(int $employeeId, WorkShift $workShift): void;

    public function findActiveDelegation(int $employeeId): ?Delegation;
    public function saveDelegation(int $employeeId, Delegation $delegation): void;
    public function deleteActiveDelegation(int $employeeId): void;
}
