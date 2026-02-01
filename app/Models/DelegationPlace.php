<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DelegationPlace extends Model
{
    protected $fillable = [
        'google_place_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'photo_reference',
    ];
}
