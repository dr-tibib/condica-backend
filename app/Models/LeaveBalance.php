<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class LeaveBalance extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'total_entitlement',
        'carried_over',
        'taken',
    ];

    protected $casts = [
        'total_entitlement' => 'float',
        'carried_over' => 'float',
        'taken' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
