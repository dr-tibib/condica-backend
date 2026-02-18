<?php

declare(strict_types=1);

namespace App\Core\UseCases;

use App\Core\Contracts\Repositories\IVehicleRepository;
use App\Core\Contracts\Services\ILocationService;
use App\Core\Entities\Vehicle;

class DelegationWizardInteractor
{
    public function __construct(
        private ILocationService $locationService,
        private IVehicleRepository $vehicleRepository
    ) {}

    /**
     * @return array<int, array{place_id: string, name: string, address: string, lat: float, lng: float}>
     */
    public function searchLocations(string $query): array
    {
        return $this->locationService->search($query);
    }

    /**
     * @return Vehicle[]
     */
    public function getAvailableVehicles(): array
    {
        return $this->vehicleRepository->findAll();
    }
}
