<?php

declare(strict_types=1);

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'place_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'start_event_id',
        'end_event_id',
        'vehicle_id',
        'delegation_place_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function startEvent(): BelongsTo
    {
        return $this->belongsTo(PresenceEvent::class, 'start_event_id');
    }

    public function endEvent(): BelongsTo
    {
        return $this->belongsTo(PresenceEvent::class, 'end_event_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function delegationPlace(): BelongsTo
    {
        return $this->belongsTo(DelegationPlace::class);
    }
}
