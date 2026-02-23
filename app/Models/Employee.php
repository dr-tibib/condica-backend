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

    protected $appends = ['name', 'avatar_url'];

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

    // --- Methods updated for Refactored PresenceEvent ---

    /**
     * Get the latest presence event for this employee.
     */
    public function latestPresenceEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)->latestOfMany('start_at');
    }

    /**
     * Get the latest presence event for this employee (including check-in/out logic).
     */
    public function latestCheckinCheckoutPresenceEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)
            ->where('type', 'presence')
            ->latestOfMany('start_at');
    }

    /**
     * Get the latest check-in (active or completed).
     */
    public function latestCheckIn(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->latestCheckinCheckoutPresenceEvent();
    }

    /**
     * Check if the employee is currently present (has an active presence event).
     */
    public function isCurrentlyPresent(): bool
    {
        return $this->presenceEvents()
            ->where('type', 'presence')
            ->active()
            ->exists();
    }

    /**
     * Get the workplace where the employee is currently present.
     */
    public function getCurrentWorkplace(): ?Workplace
    {
        $activePresence = $this->presenceEvents()
            ->where('type', 'presence')
            ->active()
            ->latest('start_at')
            ->first();

        return $activePresence ? $activePresence->workplace : null;
    }

    /**
     * Get total minutes worked today across all workplaces.
     */
    public function getTodayMinutes(): int
    {
        $events = $this->presenceEvents()
            ->whereDate('start_at', today())
            ->get();

        return $this->calculateMinutesFromEvents($events);
    }

    /**
     * Get total minutes worked this week across all workplaces.
     */
    public function getWeekMinutes(): int
    {
        $events = $this->presenceEvents()
            ->whereBetween('start_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
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

        foreach ($events as $event) {
            if ($event->end_at) {
                $totalMinutes += (int) $event->start_at->diffInMinutes($event->end_at);
            } elseif ($event->start_at->isToday()) {
                $totalMinutes += (int) $event->start_at->diffInMinutes(now());
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
            return $this->avatar;
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->first_name . ' ' . $this->last_name) . '&color=7F9CF5&background=EBF4FF';
    }
}
