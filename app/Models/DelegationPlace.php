<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class DelegationPlace extends Model
{
    use CrudTrait;

    protected $fillable = [
        'google_place_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'photo_reference',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo_reference) {
            return null;
        }

        $apiKey = config('services.google_places.key');

        if (str_starts_with($this->photo_reference, 'http')) {
            return $this->photo_reference;
        }

        if (str_starts_with($this->photo_reference, 'places/')) {
            // New Places API format
            return "https://places.googleapis.com/v1/{$this->photo_reference}/media?maxHeightPx=400&maxWidthPx=400&key={$apiKey}";
        }

        // Old Places API format
        return "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photo_reference={$this->photo_reference}&key={$apiKey}";
    }
}
