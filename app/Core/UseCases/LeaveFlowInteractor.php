<?php

declare(strict_types=1);

namespace App\Core\UseCases;

use App\Core\Contracts\HolidayProvider;
use App\Core\Contracts\Repositories\IEmployeeRepository;
use App\Core\Contracts\Repositories\ILeaveRepository;
use App\Core\Contracts\Repositories\IPresenceRepository;
use App\Core\Contracts\Repositories\ITransactionManager;
use App\Core\Entities\Delegation;
use App\Core\Entities\Leave;
use App\Core\Entities\WorkShift;
use App\Core\UseCases\Contracts\LeaveFlowInputPort;
use App\Core\UseCases\DTOs\LeaveFlowOutput;
use DateTimeInterface;

class LeaveFlowInteractor implements LeaveFlowInputPort
{
    public function __construct(
        private IEmployeeRepository $employeeRepository,
        private ILeaveRepository $leaveRepository,
        private IPresenceRepository $presenceRepository,
        private ITransactionManager $transactionManager,
        private HolidayProvider $holidayProvider
    ) {}

    public function execute(
        string $workplaceEnterCode,
        DateTimeInterface $startDate,
        int $days,
        DateTimeInterface $currentTime
    ): LeaveFlowOutput {
        $employee = $this->employeeRepository->findByCode($workplaceEnterCode);
        if (! $employee) {
            throw new \Exception("Employee not found");
        }

        $employeeId = $employee->getId();

        // Calculate End Date
        $endDate = Leave::calculateEndDate($startDate, $days, $this->holidayProvider);

        return $this->transactionManager->transaction(function () use ($employeeId, $startDate, $endDate, $currentTime) {
            // Close Active WorkShift
            $activeShift = $this->presenceRepository->findActiveWorkShift($employeeId);
            if ($activeShift) {
                $this->presenceRepository->saveWorkShift(
                    $employeeId,
                    new WorkShift($activeShift->getStartTime(), $currentTime, $activeShift->getId())
                );
            }

            // Close Active Delegation
            $activeDelegation = $this->presenceRepository->findActiveDelegation($employeeId);
            if ($activeDelegation) {
                $this->presenceRepository->saveDelegation(
                    $employeeId,
                    new Delegation(
                        $activeDelegation->getStartTime(),
                        $currentTime,
                        $activeDelegation->getId(),
                        $activeDelegation->getVehicleId(),
                        $activeDelegation->getLocations()
                    )
                );
            }

            // Create Leave
            $leave = new Leave($startDate, $endDate);
            $this->leaveRepository->saveLeave($employeeId, $leave);

            return new LeaveFlowOutput(true, "Leave created successfully");
        });
    }
}
