<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'place_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'start_event_id',
        'end_event_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function startEvent(): BelongsTo
    {
        return $this->belongsTo(PresenceEvent::class, 'start_event_id');
    }

    public function endEvent(): BelongsTo
    {
        return $this->belongsTo(PresenceEvent::class, 'end_event_id');
    }
}
