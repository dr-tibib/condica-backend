<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelegationStop extends Model
{
    protected $fillable = [
        'delegation_id',
        'delegation_place_id',
        'place_id',
        'name',
        'address',
        'latitude',
        'longitude',
    ];

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    public function delegationPlace(): BelongsTo
    {
        return $this->belongsTo(DelegationPlace::class);
    }

    public function getPhotoUrlAttribute()
    {
        return $this->delegationPlace?->photo_url;
    }
}
