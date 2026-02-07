<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use CrudTrait, HasFactory, LogsActivity;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'id_document_type',
        'id_document_number',
        'personal_numeric_code',
        'workplace_enter_code',
        'avatar',
        'user_id',
        'manager_id',
        'department_id',
        'workplace_id',
    ];

    /**
     * Get the user associated with the employee.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager of the employee.
     */
    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the subordinates of the employee.
     */
    public function subordinates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get the department of the employee.
     */
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the default workplace of the employee.
     */
    public function workplace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    /**
     * Get all presence events for this employee.
     */
    public function presenceEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresenceEvent::class);
    }

    /**
     * Get all delegations for this employee.
     */
    public function delegations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Delegation::class);
    }

    /**
     * Get all leave requests for this employee.
     */
    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get all devices registered to this employee.
     */
    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Device::class);
    }

    // --- Methods moved from User ---

    /**
     * Get the latest presence event for this employee.
     */
    public function latestPresenceEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)->latestOfMany('event_time');
    }

    /**
     * Get the latest presence event for this employee.
     */
    public function latestCheckinCheckoutPresenceEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)
            ->ofMany([
                'event_time' => 'max'
            ], function (Builder $query) {
                $query->whereIn('event_type', ['check_in', 'check_out']);
            });
    }

    /**
     * Get the latest check-in event for this employee.
     */
    public function latestCheckIn(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)
            ->whereIn('event_type', ['check_in', 'delegation_start'])
            ->latestOfMany('event_time');
    }

    /**
     * Get the latest check-out event for this employee.
     */
    public function latestCheckOut(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)
            ->whereIn('event_type', ['check_out', 'delegation_end'])
            ->latestOfMany('event_time');
    }

    /**
     * Check if the employee is currently present (last event was a check-in).
     */
    public function isCurrentlyPresent(): bool
    {
        $latestEvent = $this->latestCheckinCheckoutPresenceEvent;

        return $latestEvent && in_array($latestEvent->event_type, ['check_in']);
    }

    /**
     * Get the workplace where the employee is currently present.
     */
    public function getCurrentWorkplace(): ?Workplace
    {
        if (! $this->isCurrentlyPresent()) {
            return null;
        }

        return $this->latestCheckinCheckoutPresenceEvent->workplace;
    }

    /**
     * Get total minutes worked today across all workplaces.
     */
    public function getTodayMinutes(): int
    {
        $events = $this->presenceEvents()
            ->whereDate('event_time', today())
            ->orderBy('event_time')
            ->get();

        return $this->calculateMinutesFromEvents($events);
    }

    /**
     * Get total minutes worked this week across all workplaces.
     */
    public function getWeekMinutes(): int
    {
        $events = $this->presenceEvents()
            ->whereBetween('event_time', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->orderBy('event_time')
            ->get();

        return $this->calculateMinutesFromEvents($events);
    }

    /**
     * Calculate total minutes from a collection of presence events.
     *
     * @param  \Illuminate\Support\Collection<int, PresenceEvent>  $events
     */
    private function calculateMinutesFromEvents(\Illuminate\Support\Collection $events): int
    {
        $totalMinutes = 0;
        $currentCheckIn = null;

        foreach ($events as $event) {
            if ($event->isCheckIn()) {
                $currentCheckIn = $event;
            } elseif ($event->isCheckOut() && $currentCheckIn !== null) {
                $totalMinutes += (int) $currentCheckIn->event_time->diffInMinutes($event->event_time);
                $currentCheckIn = null;
            }
        }

        return $totalMinutes;
    }

    /**
     * Get the full name of the employee.
     */
    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the avatar URL or a placeholder.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            // Assuming Backpack stores the path relative to storage disk
            // You might need Storage::url() here if using public disk
            return $this->avatar;
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->first_name . ' ' . $this->last_name) . '&color=7F9CF5&background=EBF4FF';
    }
}
