<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class LeaveType extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'name',
        'is_paid',
        'requires_document',
        'affects_annual_quota',
        'medical_code_required',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_document' => 'boolean',
        'affects_annual_quota' => 'boolean',
        'medical_code_required' => 'boolean',
    ];
}
