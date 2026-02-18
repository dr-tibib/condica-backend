<?php

declare(strict_types=1);

namespace App\Core\UseCases;

use App\Core\Contracts\HolidayProvider;
use App\Core\Contracts\Repositories\IEmployeeRepository;
use App\Core\Contracts\Repositories\ILeaveRepository;
use App\Core\Contracts\Repositories\IPresenceRepository;
use App\Core\Contracts\Repositories\ITransactionManager;
use App\Core\Entities\Leave;
use App\Core\Entities\WorkShift;
use App\Core\UseCases\Contracts\NormalFlowInputPort;
use App\Core\UseCases\DTOs\NormalFlowOutput;
use App\Core\UseCases\DTOs\RefinementList;
use App\Core\UseCases\DTOs\Requirement;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;

class NormalFlowInteractor implements NormalFlowInputPort
{
    public function __construct(
        private IEmployeeRepository $employeeRepository,
        private IPresenceRepository $presenceRepository,
        private ILeaveRepository $leaveRepository,
        private ITransactionManager $transactionManager,
        private HolidayProvider $holidayProvider
    ) {}

    public function execute(string $workplaceEnterCode, DateTimeInterface $currentTime): NormalFlowOutput
    {
        $employee = $this->employeeRepository->findByCode($workplaceEnterCode);
        if (! $employee) {
            throw new \Exception("Employee not found");
        }

        $employeeId = $employee->getId();

        // Check Leave
        $activeLeave = $this->leaveRepository->findActiveLeave($employeeId, $currentTime);
        if ($activeLeave) {
            return $this->handleLeaveSplit($employeeId, $activeLeave, $currentTime);
        }

        $activeShift = $this->presenceRepository->findActiveWorkShift($employeeId);

        if (! $activeShift) {
            // Check 16:00
            $hour = (int) $currentTime->format('H');
            if ($hour >= 16) {
                return new NormalFlowOutput(new Requirement(Requirement::TYPE_ASK_START_OR_END));
            }

            // Start Shift
            $this->presenceRepository->saveWorkShift($employeeId, new WorkShift($currentTime));
            return new NormalFlowOutput(null, null, true, false, false);
        }

        // Active Shift
        if ($activeShift->isOvernight($currentTime)) {
            $refinementItems = $this->generateRefinementItems($activeShift->getStartTime(), $currentTime);
            return new NormalFlowOutput(null, new RefinementList($refinementItems));
        }

        // Close Shift
        $closedShift = new WorkShift(
            $activeShift->getStartTime(),
            $currentTime,
            $activeShift->getId()
        );
        $this->presenceRepository->saveWorkShift($employeeId, $closedShift);

        return new NormalFlowOutput(null, null, false, true, false);
    }

    private function handleLeaveSplit(int $employeeId, Leave $activeLeave, DateTimeInterface $currentTime): NormalFlowOutput
    {
        return $this->transactionManager->transaction(function () use ($employeeId, $activeLeave, $currentTime) {
            $startDate = DateTimeImmutable::createFromInterface($activeLeave->getStartDate());
            $originalEndDate = DateTimeImmutable::createFromInterface($activeLeave->getEndDate());

            // Part A: Close at Yesterday
            $yesterday = DateTimeImmutable::createFromInterface($currentTime)
                ->modify('-1 day')
                ->setTime(23, 59, 59);

            if ($yesterday < $startDate) {
                // Leave started today or later (but strictly today since active)
                // Cancel leave
                $this->leaveRepository->deleteLeave($employeeId, $activeLeave);
            } else {
                // Update Part A
                $partA = new Leave($startDate, $yesterday, $activeLeave->getId());
                $this->leaveRepository->saveLeave($employeeId, $partA);
            }

            // Part B: Start Tomorrow
            $tomorrow = DateTimeImmutable::createFromInterface($currentTime)
                ->modify('+1 day')
                ->setTime(0, 0, 0);

            if ($tomorrow <= $originalEndDate) {
                $partB = new Leave($tomorrow, $originalEndDate); // New Leave
                $this->leaveRepository->saveLeave($employeeId, $partB);
            }

            // Start WorkShift
            $this->presenceRepository->saveWorkShift($employeeId, new WorkShift($currentTime));

            return new NormalFlowOutput(null, null, true, false, true);
        });
    }

    private function generateRefinementItems(DateTimeInterface $start, DateTimeInterface $now): array
    {
        $startDate = DateTimeImmutable::createFromInterface($start)->setTime(0, 0, 0);
        $yesterday = DateTimeImmutable::createFromInterface($now)->modify('-1 day')->setTime(0, 0, 0);

        if ($startDate > $yesterday) {
            return [];
        }

        $period = new DatePeriod(
            $startDate,
            new DateInterval('P1D'),
            $yesterday->modify('+1 day')
        );

        $items = [];
        foreach ($period as $dt) {
            /** @var DateTimeInterface $dt */
            if ((int) $dt->format('N') >= 6 || $this->holidayProvider->isHoliday($dt)) {
                continue;
            }

            $items[] = [
                'date' => $dt->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00'
            ];
        }

        return $items;
    }
}
