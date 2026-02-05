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
    ];
}
