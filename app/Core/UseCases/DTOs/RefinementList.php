<?php

declare(strict_types=1);

namespace App\Core\UseCases\DTOs;

class RefinementList
{
    /** @var array<array{date: string, start_time: ?string, end_time: ?string}> */
    public array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }
}
