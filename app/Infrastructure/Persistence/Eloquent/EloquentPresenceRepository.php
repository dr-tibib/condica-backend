<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Core\Contracts\Repositories\IPresenceRepository;
use App\Core\Entities\Delegation as CoreDelegation;
use App\Core\Entities\WorkShift as CoreWorkShift;
use App\Models\Delegation as EloquentDelegation;
use App\Models\DelegationPlace;
use App\Models\PresenceEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class EloquentPresenceRepository implements IPresenceRepository
{
    public function findActiveWorkShift(int $employeeId): ?CoreWorkShift
    {
        /** @var PresenceEvent|null $event */
        $event = PresenceEvent::query()
            ->where('employee_id', $employeeId)
            ->where('event_type', 'check_in')
            ->whereNull('pair_event_id')
            ->latest('event_time')
            ->first();

        if (! $event) {
            return null;
        }

        return new CoreWorkShift(
            $event->event_time,
            null,
            $event->id
        );
    }

    public function findLastWorkShift(int $employeeId): ?CoreWorkShift
    {
        /** @var PresenceEvent|null $event */
        $event = PresenceEvent::query()
            ->where('employee_id', $employeeId)
            ->where('event_type', 'check_in')
            ->latest('event_time')
            ->with('pairedEvent')
            ->first();

        if (! $event) {
            return null;
        }

        $endTime = $event->pairedEvent?->event_time;

        return new CoreWorkShift(
            $event->event_time,
            $endTime,
            $event->id
        );
    }

    public function saveWorkShift(int $employeeId, CoreWorkShift $workShift): void
    {
        if ($workShift->getId() === null) {
            // New WorkShift -> Create CheckIn
            PresenceEvent::create([
                'employee_id' => $employeeId,
                'event_type' => 'check_in',
                'event_time' => $workShift->getStartTime(),
                'method' => 'manual', // or detect from context?
            ]);
        } else {
            // Update Existing WorkShift
            /** @var PresenceEvent $checkInEvent */
            $checkInEvent = PresenceEvent::findOrFail($workShift->getId());

            // If updating EndTime
            if ($workShift->getEndTime() !== null) {
                // Create CheckOut
                $checkOutEvent = PresenceEvent::create([
                    'employee_id' => $employeeId,
                    'event_type' => 'check_out',
                    'event_time' => $workShift->getEndTime(),
                    'pair_event_id' => $checkInEvent->id,
                    'method' => 'manual',
                ]);

                // Link CheckIn to CheckOut
                $checkInEvent->update(['pair_event_id' => $checkOutEvent->id]);
            }
        }
    }

    public function findActiveDelegation(int $employeeId): ?CoreDelegation
    {
        /** @var EloquentDelegation|null $delegationModel */
        $delegationModel = EloquentDelegation::query()
            ->where('employee_id', $employeeId)
            ->whereNull('end_event_id')
            ->with(['startEvent', 'vehicle', 'delegationPlace'])
            ->latest()
            ->first();

        if (! $delegationModel || ! $delegationModel->startEvent) {
            return null;
        }

        $locations = [];
        if ($delegationModel->delegationPlace) {
            $locations[] = [
                'name' => $delegationModel->delegationPlace->name,
                'address' => $delegationModel->delegationPlace->address,
                'lat' => $delegationModel->delegationPlace->latitude,
                'lng' => $delegationModel->delegationPlace->longitude,
            ];
        } elseif ($delegationModel->address) {
            $locations[] = [
                'name' => $delegationModel->name,
                'address' => $delegationModel->address,
                'lat' => $delegationModel->latitude,
                'lng' => $delegationModel->longitude,
            ];
        }

        return new CoreDelegation(
            $delegationModel->startEvent->event_time,
            null,
            $delegationModel->id,
            $delegationModel->vehicle_id,
            $locations
        );
    }

    public function saveDelegation(int $employeeId, CoreDelegation $delegation): void
    {
        if ($delegation->getId() === null) {
            // Start Delegation
            // 1. Create Delegation Start Event
            $startEvent = PresenceEvent::create([
                'employee_id' => $employeeId,
                'event_type' => 'delegation_start',
                'event_time' => $delegation->getStartTime(),
                'method' => 'manual',
            ]);

            // 2. Handle Location (Assuming first location)
            $locations = $delegation->getLocations();
            $firstLoc = $locations[0] ?? [];

            // Should verify if delegationPlace exists or create/find it?
            // For now, assuming simple mapping.
            $placeId = $firstLoc['place_id'] ?? null;
            $delegationPlaceId = null;
            if ($placeId) {
                $delegationPlace = DelegationPlace::firstOrCreate(
                    ['google_place_id' => $placeId],
                    [
                        'name' => $firstLoc['name'] ?? '',
                        'address' => $firstLoc['address'] ?? '',
                        'latitude' => $firstLoc['lat'] ?? 0.0,
                        'longitude' => $firstLoc['lng'] ?? 0.0,
                    ]
                );
                $delegationPlaceId = $delegationPlace->id;
            }

            // 3. Create Delegation Model
            EloquentDelegation::create([
                'employee_id' => $employeeId,
                'start_event_id' => $startEvent->id,
                'vehicle_id' => $delegation->getVehicleId(),
                'delegation_place_id' => $delegationPlaceId,
                'name' => $firstLoc['name'] ?? null,
                'address' => $firstLoc['address'] ?? null,
                'latitude' => $firstLoc['lat'] ?? null,
                'longitude' => $firstLoc['lng'] ?? null,
            ]);

        } else {
            // End Delegation
            /** @var EloquentDelegation $delegationModel */
            $delegationModel = EloquentDelegation::findOrFail($delegation->getId());

            if ($delegation->getEndTime() !== null) {
                // 1. Create Delegation End Event
                $endEvent = PresenceEvent::create([
                    'employee_id' => $employeeId,
                    'event_type' => 'delegation_end',
                    'event_time' => $delegation->getEndTime(),
                    'pair_event_id' => $delegationModel->start_event_id,
                    'method' => 'manual',
                ]);

                // 2. Update Start Event Pair
                if ($delegationModel->startEvent) {
                    $delegationModel->startEvent->update(['pair_event_id' => $endEvent->id]);
                }

                // 3. Update Delegation Model
                $delegationModel->update(['end_event_id' => $endEvent->id]);
            }
        }
    }

    public function deleteActiveDelegation(int $employeeId): void
    {
        // Find active delegation
        $delegationModel = EloquentDelegation::query()
            ->where('employee_id', $employeeId)
            ->whereNull('end_event_id')
            ->latest()
            ->first();

        if ($delegationModel) {
            // Delete Start Event
            if ($delegationModel->start_event_id) {
                PresenceEvent::destroy($delegationModel->start_event_id);
            }
            // Delete Delegation Model
            $delegationModel->delete();
        }
    }
}
