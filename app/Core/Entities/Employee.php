<?php

declare(strict_types=1);

namespace App\Core\Entities;

class Employee
{
    private int $id;
    private string $code;

    public function __construct(int $id, string $code)
    {
        $this->id = $id;
        $this->code = $code;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
