<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Contracts\Syncable;

class User extends Authenticatable implements Syncable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use CrudTrait, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_global_superadmin',
        'default_workplace_id',
        'employee_id',
        'department',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_global_superadmin' => 'boolean',
        ];
    }

    public function getGlobalIdentifierKey(): string
    {
        return $this->getAttribute($this->getGlobalIdentifierKeyName());
    }

    public function getGlobalIdentifierKeyName(): string
    {
        return 'email';
    }

    public function getCentralModelName(): string
    {
        return CentralUser::class;
    }

    public function getSyncedAttributeNames(): array
    {
        return [
            'name',
            'email',
            'password',
            'is_global_superadmin',
        ];
    }

    public function triggerSyncEvent()
    {
        // This method is required by the Syncable interface,
        // but we don't want to sync from Tenant to Central.
        // So we leave this empty.
    }

    /**
     * Get all presence events for this user.
     */
    public function presenceEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresenceEvent::class);
    }

    /**
     * Get the latest presence event for this user.
     */
    public function latestPresenceEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)->latestOfMany('event_time');
    }

    /**
     * Get the latest check-in event for this user.
     */
    public function latestCheckIn(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)
            ->where('event_type', 'check_in')
            ->latestOfMany('event_time');
    }

    /**
     * Get the latest check-out event for this user.
     */
    public function latestCheckOut(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PresenceEvent::class)
            ->where('event_type', 'check_out')
            ->latestOfMany('event_time');
    }

    /**
     * Get all devices registered to this user.
     */
    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Get the default workplace for this user.
     */
    public function defaultWorkplace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workplace::class, 'default_workplace_id');
    }

    /**
     * Check if the user is currently present (last event was a check-in).
     */
    public function isCurrentlyPresent(): bool
    {
        $latestEvent = $this->latestPresenceEvent;

        return $latestEvent?->event_type === 'check_in';
    }

    /**
     * Get the workplace where the user is currently present.
     */
    public function getCurrentWorkplace(): ?Workplace
    {
        if (! $this->isCurrentlyPresent()) {
            return null;
        }

        return $this->latestPresenceEvent->workplace;
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
            if ($event->event_type === 'check_in') {
                $currentCheckIn = $event;
            } elseif ($event->event_type === 'check_out' && $currentCheckIn !== null) {
                $totalMinutes += (int) $currentCheckIn->event_time->diffInMinutes($event->event_time);
                $currentCheckIn = null;
            }
        }

        return $totalMinutes;
    }
}
