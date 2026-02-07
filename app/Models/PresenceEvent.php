<?php

declare(strict_types=1);

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceEvent extends Model
{
    use CrudTrait;

    /** @use HasFactory<\Database\Factories\PresenceEventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'workplace_id',
        'event_type',
        'event_time',
        'method',
        'latitude',
        'longitude',
        'accuracy',
        'device_info',
        'app_version',
        'notes',
        'pair_event_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'device_info' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy' => 'integer',
        ];
    }

    /**
     * Get the employee that owns this presence event.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the workplace for this presence event.
     */
    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    /**
     * Get the paired event (check-out linked to check-in or vice versa).
     */
    public function pairedEvent(): BelongsTo
    {
        return $this->belongsTo(PresenceEvent::class, 'pair_event_id');
    }

    /**
     * Scope a query to only include check-in events.
     */
    public function scopeCheckIns(Builder $query): void
    {
        $query->whereIn('event_type', ['check_in', 'delegation_start']);
    }

    /**
     * Scope a query to only include check-out events.
     */
    public function scopeCheckOuts(Builder $query): void
    {
        $query->whereIn('event_type', ['check_out', 'delegation_end']);
    }

    /**
     * Scope a query to only include today's events.
     */
    public function scopeToday(Builder $query): void
    {
        $query->whereDate('event_time', today());
    }

    /**
     * Scope a query to only include events for a specific employee.
     */
    public function scopeForEmployee(Builder $query, int $employeeId): void
    {
        $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to only include events for a specific workplace.
     */
    public function scopeForWorkplace(Builder $query, int $workplaceId): void
    {
        $query->where('workplace_id', $workplaceId);
    }

    /**
     * Scope a query to only include automatic events.
     */
    public function scopeAutomatic(Builder $query): void
    {
        $query->where('method', 'auto');
    }

    /**
     * Scope a query to only include manual events.
     */
    public function scopeManual(Builder $query): void
    {
        $query->where('method', 'manual');
    }

    /**
     * Check if this event is a check-in.
     */
    public function isCheckIn(): bool
    {
        return in_array($this->event_type, ['check_in', 'delegation_start']);
    }

    /**
     * Check if this event is a check-out.
     */
    public function isCheckOut(): bool
    {
        return in_array($this->event_type, ['check_out', 'delegation_end']);
    }

    public function isDelegationStart(): bool
    {
        return $this->event_type === 'delegation_start';
    }

    public function isDelegationEnd(): bool
    {
        return $this->event_type === 'delegation_end';
    }

    /**
     * Get the paired event (loads if not already loaded).
     */
    public function getPairedEvent(): ?PresenceEvent
    {
        if ($this->pair_event_id === null) {
            return null;
        }

        return $this->pairedEvent()->first();
    }

    /**
     * Calculate the duration in minutes between this event and its paired event.
     *
     * @return int|null Duration in minutes, or null if no paired event exists
     */
    public function getDurationMinutes(): ?int
    {
        $paired = $this->getPairedEvent();

        if ($paired === null) {
            return null;
        }

        if ($this->isCheckIn()) {
            return (int) $this->event_time->diffInMinutes($paired->event_time);
        }

        return (int) $paired->event_time->diffInMinutes($this->event_time);
    }
}
