<?php

declare(strict_types=1);

namespace App\Core\Contracts\Services;

interface ILocationService
{
    /**
     * Search for locations based on a query.
     *
     * @param string $query
     * @return array<int, array{place_id: string, name: string, address: string, lat: float, lng: float}>
     */
    public function search(string $query): array;
}
