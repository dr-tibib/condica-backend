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
        'employee_id',
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

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
