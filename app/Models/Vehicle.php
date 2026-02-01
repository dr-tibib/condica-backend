<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'license_plate',
    ];
}
