<?php

declare(strict_types=1);

namespace App\Core\UseCases\DTOs;

class Requirement
{
    public const TYPE_ASK_START_OR_END = 'ASK_START_OR_END';

    public string $type;
    public ?array $data;

    public function __construct(string $type, ?array $data = null)
    {
        $this->type = $type;
        $this->data = $data;
    }
}
