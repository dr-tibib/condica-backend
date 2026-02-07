<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class LeaveRequest extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'approver_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'medical_certificate_series',
        'medical_certificate_number',
        'medical_code',
        'attachment_path',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
