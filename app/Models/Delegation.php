<?php

declare(strict_types=1);

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Delegation extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'presence_event_id',
        'place_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'vehicle_id',
        'delegation_place_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the presence event that started/contains this delegation.
     */
    public function presenceEvent(): BelongsTo
    {
        return $this->belongsTo(PresenceEvent::class);
    }

    /**
     * Reciprocal link back to the presence event as a linkable.
     */
    public function eventLink(): MorphOne
    {
        return $this->morphOne(PresenceEvent::class, 'linkable');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function delegationPlace(): BelongsTo
    {
        return $this->belongsTo(DelegationPlace::class);
    }

    public function stops(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DelegationStop::class);
    }

    /**
     * logic: isMultiDay(): True if start_date and end_date differ.
     */
    public function isMultiDay(?\Carbon\Carbon $now = null): bool
    {
        $start = $this->presenceEvent?->start_at;
        if (!$start) return false;
        
        $end = $this->presenceEvent?->end_at ?? $now ?? \Carbon\Carbon::now();
        return ! $start->isSameDay($end);
    }

    /**
     * logic: isCancellable(): True if duration < 10 mins.
     */
    public function isCancellable(?\Carbon\Carbon $now = null): bool
    {
        $start = $this->presenceEvent?->start_at;
        if (!$start) return true;

        $end = $this->presenceEvent?->end_at ?? $now ?? \Carbon\Carbon::now();
        return $start->diffInMinutes($end) < 10;
    }

    /**
     * logic: generateRefinementTimeline(): Generates a list of days between start and end, excluding weekends ( and ), pre-filled with Workplace default hours.
     *
     * @return array<int, array{date: string, start_time: string, end_time: string}>
     */
    public function generateRefinementTimeline(string $defaultStart, string $defaultEnd, ?\Carbon\Carbon $until = null): array
    {
        $start = $this->presenceEvent?->start_at;
        if (!$start) return [];

        $timeline = [];
        $end = $this->presenceEvent?->end_at ?? $until ?? \Carbon\Carbon::now();

        $period = \Carbon\CarbonPeriod::create($start, '1 day', $end);

        foreach ($period as $date) {
            /** @var \Carbon\Carbon $date */
            if ($date->isWeekend()) {
                continue;
            }

            $timeline[] = [
                'date' => $date->format('Y-m-d'),
                'start_time' => $defaultStart,
                'end_time' => $defaultEnd,
            ];
        }

        return $timeline;
    }
}
