<?php

declare(strict_types=1);

namespace App\Core\UseCases;

use App\Core\Contracts\HolidayProvider;
use App\Core\Contracts\Repositories\IEmployeeRepository;
use App\Core\Contracts\Repositories\ILeaveRepository;
use App\Core\Contracts\Repositories\IPresenceRepository;
use App\Core\Contracts\Repositories\ITransactionManager;
use App\Core\Contracts\Repositories\IVehicleRepository;
use App\Core\Entities\Delegation;
use App\Core\Entities\Leave;
use App\Core\Entities\WorkShift;
use App\Core\UseCases\Contracts\DelegationFlowInputPort;
use App\Core\UseCases\DTOs\DelegationFlowOutput;
use App\Core\UseCases\DTOs\DelegationTimeline;
use DateTimeImmutable;
use DateTimeInterface;

class DelegationFlowInteractor implements DelegationFlowInputPort
{
    public function __construct(
        private IEmployeeRepository $employeeRepository,
        private IPresenceRepository $presenceRepository,
        private ILeaveRepository $leaveRepository,
        private IVehicleRepository $vehicleRepository,
        private ITransactionManager $transactionManager,
        private HolidayProvider $holidayProvider
    ) {}

    public function start(
        string $workplaceEnterCode,
        int $vehicleId,
        array $locations,
        DateTimeInterface $currentTime
    ): DelegationFlowOutput {
        $employee = $this->employeeRepository->findByCode($workplaceEnterCode);
        if (! $employee) {
            throw new \Exception("Employee not found");
        }

        $vehicle = $this->vehicleRepository->findById($vehicleId);
        if (! $vehicle) {
            throw new \Exception("Vehicle not found");
        }

        return $this->transactionManager->transaction(function () use ($employee, $vehicleId, $locations, $currentTime) {
            $employeeId = $employee->getId();

            // Close Active WorkShift
            $activeShift = $this->presenceRepository->findActiveWorkShift($employeeId);
            if ($activeShift) {
                $this->presenceRepository->saveWorkShift(
                    $employeeId,
                    new WorkShift($activeShift->getStartTime(), $currentTime, $activeShift->getId())
                );
            }

            // Close Active Leave
            $activeLeave = $this->leaveRepository->findActiveLeave($employeeId, $currentTime);
            if ($activeLeave) {
                $this->processLeaveInterruption($employeeId, $activeLeave, $currentTime);
            }

            // Create Delegation
            $delegation = new Delegation($currentTime, null, null, $vehicleId, $locations);
            $this->presenceRepository->saveDelegation($employeeId, $delegation);

            return new DelegationFlowOutput(true, false, false, null);
        });
    }

    public function end(string $workplaceEnterCode, DateTimeInterface $currentTime): DelegationFlowOutput
    {
        $employee = $this->employeeRepository->findByCode($workplaceEnterCode);
        if (! $employee) {
            throw new \Exception("Employee not found");
        }

        $employeeId = $employee->getId();
        $activeDelegation = $this->presenceRepository->findActiveDelegation($employeeId);

        if (! $activeDelegation) {
            // If no active delegation, maybe throw error or just return false?
            // Assuming strict flow: Exception.
            throw new \Exception("No active delegation to end");
        }

        // Check Cancellable (< 10 mins)
        if ($activeDelegation->isCancellable($currentTime)) {
            return $this->transactionManager->transaction(function () use ($employeeId, $activeDelegation) {
                // Rollback: Delete Delegation
                $this->presenceRepository->deleteActiveDelegation($employeeId);

                // Re-open last closed WorkShift
                $lastShift = $this->presenceRepository->findLastWorkShift($employeeId);
                if ($lastShift && $lastShift->getEndTime()) {
                    // Assuming "same day" check is implied or we just re-open the last one regardless?
                    // "re-open the previously closed WorkShift".
                    // I'll re-open it.
                    $reopenedShift = new WorkShift($lastShift->getStartTime(), null, $lastShift->getId());
                    $this->presenceRepository->saveWorkShift($employeeId, $reopenedShift);
                }

                return new DelegationFlowOutput(false, false, true, null);
            });
        }

        // Multi-day Check
        if ($activeDelegation->isMultiDay($currentTime)) {
            return $this->transactionManager->transaction(function () use ($employeeId, $activeDelegation, $currentTime) {
                // End Delegation
                $endedDelegation = new Delegation(
                    $activeDelegation->getStartTime(),
                    $currentTime,
                    $activeDelegation->getId(),
                    $activeDelegation->getVehicleId(),
                    $activeDelegation->getLocations()
                );
                $this->presenceRepository->saveDelegation($employeeId, $endedDelegation);

                // Start WorkShift
                $this->presenceRepository->saveWorkShift($employeeId, new WorkShift($currentTime));

                // Generate Timeline
                $timelineItems = $activeDelegation->generateRefinementTimeline(
                    $this->holidayProvider,
                    '09:00',
                    '17:00',
                    $currentTime
                );

                return new DelegationFlowOutput(false, true, false, new DelegationTimeline($timelineItems));
            });
        }

        // Normal End
        return $this->transactionManager->transaction(function () use ($employeeId, $activeDelegation, $currentTime) {
            $endedDelegation = new Delegation(
                $activeDelegation->getStartTime(),
                $currentTime,
                $activeDelegation->getId(),
                $activeDelegation->getVehicleId(),
                $activeDelegation->getLocations()
            );
            $this->presenceRepository->saveDelegation($employeeId, $endedDelegation);

            // Start WorkShift
            $this->presenceRepository->saveWorkShift($employeeId, new WorkShift($currentTime));

            return new DelegationFlowOutput(false, true, false, null);
        });
    }

    private function processLeaveInterruption(int $employeeId, Leave $activeLeave, DateTimeInterface $currentTime): void
    {
        $startDate = DateTimeImmutable::createFromInterface($activeLeave->getStartDate());
        $originalEndDate = DateTimeImmutable::createFromInterface($activeLeave->getEndDate());

        $yesterday = DateTimeImmutable::createFromInterface($currentTime)
            ->modify('-1 day')
            ->setTime(23, 59, 59);

        if ($yesterday < $startDate) {
            $this->leaveRepository->deleteLeave($employeeId, $activeLeave);
        } else {
            $partA = new Leave($startDate, $yesterday, $activeLeave->getId());
            $this->leaveRepository->saveLeave($employeeId, $partA);
        }

        $tomorrow = DateTimeImmutable::createFromInterface($currentTime)
            ->modify('+1 day')
            ->setTime(0, 0, 0);

        if ($tomorrow <= $originalEndDate) {
            $partB = new Leave($tomorrow, $originalEndDate);
            $this->leaveRepository->saveLeave($employeeId, $partB);
        }
    }
}
