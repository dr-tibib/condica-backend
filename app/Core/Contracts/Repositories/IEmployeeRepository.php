<?php

declare(strict_types=1);

namespace App\Core\Contracts\Repositories;

use App\Core\Entities\Employee;

interface IEmployeeRepository
{
    public function findByCode(string $code): ?Employee;
    public function findById(int $id): ?Employee;
}
