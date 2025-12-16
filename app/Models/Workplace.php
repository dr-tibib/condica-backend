<?php

declare(strict_types=1);

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Workplace extends Model
{
    use CrudTrait;

    /** @use HasFactory<\Database\Factories\WorkplaceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'location_map',
        'latitude',
        'longitude',
        'radius',
        'timezone',
        'wifi_ssid',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get and set the location_map attribute for google_map field.
     * Converts between JSON format and separate latitude/longitude columns.
     */
    protected function locationMap(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value, $attributes) {
                return json_encode([
                    'lat' => (float) ($attributes['latitude'] ?? 0),
                    'lng' => (float) ($attributes['longitude'] ?? 0),
                    'formatted_address' => ''
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
            },
            set: function ($value) {
                $location = json_decode($value);
                return [
                    'latitude' => $location->lat ?? 0,
                    'longitude' => $location->lng ?? 0,
                ];
            }
        );
    }

    /**
     * Get all presence events for this workplace.
     */
    public function presenceEvents(): HasMany
    {
        return $this->hasMany(PresenceEvent::class);
    }

    /**
     * Check if a given location is within the workplace's geofence.
     */
    public function isLocationWithinGeofence(float $latitude, float $longitude): bool
    {
        if ($this->latitude === null || $this->longitude === null) {
            return false;
        }

        $distance = $this->calculateDistance($latitude, $longitude);

        return $distance <= $this->radius;
    }

    /**
     * Calculate the distance in meters between the workplace and a given location using the Haversine formula.
     */
    public function calculateDistance(float $latitude, float $longitude): float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return PHP_FLOAT_MAX;
        }

        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad((float) $this->latitude);
        $lonFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get users currently present at this workplace.
     *
     * @return Collection<int, User>
     */
    public function currentlyPresentUsers(): Collection
    {
        return User::whereHas('latestPresenceEvent', function ($query) {
            $query->where('workplace_id', $this->id)
                ->where('event_type', 'check_in');
        })->get();
    }

    /**
     * Get today's check-in events for this workplace.
     *
     * @return Collection<int, PresenceEvent>
     */
    public function todayCheckIns(): Collection
    {
        return $this->presenceEvents()
            ->checkIns()
            ->today()
            ->get();
    }

    /**
     * Get today's check-out events for this workplace.
     *
     * @return Collection<int, PresenceEvent>
     */
    public function todayCheckOuts(): Collection
    {
        return $this->presenceEvents()
            ->checkOuts()
            ->today()
            ->get();
    }
}
