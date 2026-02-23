<?php

declare(strict_types=1);

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'start_at',
        'end_at',
        'type',
        'start_method',
        'start_latitude',
        'start_longitude',
        'start_accuracy',
        'start_device_info',
        'end_method',
        'end_latitude',
        'end_longitude',
        'end_accuracy',
        'end_device_info',
        'app_version',
        'notes',
        'linkable_id',
        'linkable_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'start_device_info' => 'array',
            'end_device_info' => 'array',
            'start_latitude' => 'decimal:8',
            'start_longitude' => 'decimal:8',
            'end_latitude' => 'decimal:8',
            'end_longitude' => 'decimal:8',
            'start_accuracy' => 'integer',
            'end_accuracy' => 'integer',
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
     * Get the linked entity (Delegation, LeaveRequest, etc.)
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active events (not checked out).
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('end_at');
    }

    /**
     * Scope a query to only include today's events.
     */
    public function scopeToday(Builder $query): void
    {
        $query->whereDate('start_at', today());
    }

    /**
     * Scope a query to only include events for a specific employee.
     */
    public function scopeForEmployee(Builder $query, int $employeeId): void
    {
        $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to only include a specific type.
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Check if the event is completed.
     */
    public function isCompleted(): bool
    {
        return $this->end_at !== null;
    }

    /**
     * Calculate the duration in minutes.
     *
     * @return int|null Duration in minutes, or null if not completed
     */
    public function getDurationMinutes(): ?int
    {
        if (! $this->isCompleted()) {
            return null;
        }

        return (int) $this->start_at->diffInMinutes($this->end_at);
    }

    public function isCheckIn(): bool
    {
        return $this->type === 'presence';
    }

    public function isCheckOut(): bool
    {
        return $this->type === 'presence' && $this->end_at !== null;
    }

    public function isDelegationStart(): bool
    {
        return $this->type === 'delegation';
    }

    public function isDelegationEnd(): bool
    {
        return $this->type === 'delegation' && $this->end_at !== null;
    }

    /**
     * logic: isOvernight(): Returns true if the system clock detects a transition past 00:00 without a checkout.
     */
    public function isOvernight(?\Carbon\Carbon $now = null): bool
    {
        if ($this->end_at !== null) {
            return false;
        }

        $now = $now ?? \Carbon\Carbon::now();

        return ! $this->start_at->isSameDay($now);
    }
}
