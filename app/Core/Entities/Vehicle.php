<?php

declare(strict_types=1);

namespace App\Core\Entities;

class Vehicle
{
    private int $id;
    private string $name;
    private string $licensePlate;

    public function __construct(int $id, string $name, string $licensePlate)
    {
        $this->id = $id;
        $this->name = $name;
        $this->licensePlate = $licensePlate;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLicensePlate(): string
    {
        return $this->licensePlate;
    }
}
