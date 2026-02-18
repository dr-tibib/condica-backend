<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenters;

use App\Core\Entities\Delegation;
use App\Core\Entities\WorkShift;
use DateTimeInterface;

class PresencePresenter
{
    public function present(?WorkShift $lastShift, ?Delegation $lastDelegation): array
    {
        // Collect all potential events
        $events = [];

        if ($lastShift) {
            $events[] = [
                'type' => 'check_in',
                'time' => $lastShift->getStartTime(),
            ];
            if ($lastShift->getEndTime()) {
                $events[] = [
                    'type' => 'check_out',
                    'time' => $lastShift->getEndTime(),
                ];
            }
        }

        if ($lastDelegation) {
            $events[] = [
                'type' => 'delegation_start',
                'time' => $lastDelegation->getStartTime(),
            ];
            if ($lastDelegation->getEndTime()) {
                $events[] = [
                    'type' => 'delegation_end',
                    'time' => $lastDelegation->getEndTime(),
                ];
            }
        }

        // Sort by time descending
        usort($events, function ($a, $b) {
            /** @var DateTimeInterface $timeA */
            $timeA = $a['time'];
            /** @var DateTimeInterface $timeB */
            $timeB = $b['time'];

            // Compare timestamps
            $diff = $timeB->getTimestamp() <=> $timeA->getTimestamp();
            if ($diff !== 0) {
                return $diff;
            }

            // Break ties: Start events > End events
            $isStartA = in_array($a['type'], ['check_in', 'delegation_start']);
            $isStartB = in_array($b['type'], ['check_in', 'delegation_start']);

            // If A is start and B is end, A comes first (return -1)
            if ($isStartA && ! $isStartB) {
                return -1;
            }
            // If B is start and A is end, B comes first (return 1)
            if (! $isStartA && $isStartB) {
                return 1;
            }

            // If both are starts, prioritize delegation_start
            if ($isStartA && $isStartB) {
                if ($a['type'] === 'delegation_start') {
                    return -1;
                }
                if ($b['type'] === 'delegation_start') {
                    return 1;
                }
            }

            return 0;
        });

        $latest = $events[0] ?? null;

        if (! $latest) {
            return [
                'status' => 'absent',
                'last_entry' => null,
            ];
        }

        // Determine status
        $status = 'absent';
        if ($latest['type'] === 'check_in') {
            $status = 'present';
        } elseif ($latest['type'] === 'delegation_start') {
            $status = 'delegation';
        }

        return [
            'status' => $status,
            'last_entry' => [
                'type' => $latest['type'],
                'time' => $latest['time']->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
