<?php

declare(strict_types=1);

namespace Tests\Unit\Core\UseCases;

use App\Core\Contracts\Repositories\IVehicleRepository;
use App\Core\Contracts\Services\ILocationService;
use App\Core\Entities\Vehicle;
use App\Core\UseCases\DelegationWizardInteractor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DelegationWizardInteractorTest extends TestCase
{
    private MockObject|ILocationService $locationService;
    private MockObject|IVehicleRepository $vehicleRepository;
    private DelegationWizardInteractor $interactor;

    protected function setUp(): void
    {
        $this->locationService = $this->createMock(ILocationService::class);
        $this->vehicleRepository = $this->createMock(IVehicleRepository::class);
        $this->interactor = new DelegationWizardInteractor(
            $this->locationService,
            $this->vehicleRepository
        );
    }

    public function testSearchLocationsReturnsResults(): void
    {
        $query = 'test place';
        $results = [
            [
                'place_id' => '123',
                'name' => 'Test Place',
                'address' => '123 Test St',
                'lat' => 10.0,
                'lng' => 20.0,
            ],
        ];

        $this->locationService
            ->expects($this->once())
            ->method('search')
            ->with($query)
            ->willReturn($results);

        $this->assertEquals($results, $this->interactor->searchLocations($query));
    }

    public function testGetAvailableVehiclesReturnsVehicles(): void
    {
        $vehicles = [
            new Vehicle(1, 'Car 1', 'ABC-123'),
            new Vehicle(2, 'Car 2', 'XYZ-789'),
        ];

        $this->vehicleRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($vehicles);

        $this->assertEquals($vehicles, $this->interactor->getAvailableVehicles());
    }
}
