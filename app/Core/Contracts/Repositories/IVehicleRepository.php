<?php

declare(strict_types=1);

namespace App\Core\Contracts\Repositories;

use App\Core\Entities\Vehicle;

interface IVehicleRepository
{
    public function findById(int $id): ?Vehicle;

    /**
     * @return Vehicle[]
     */
    public function findAll(): array;
}
