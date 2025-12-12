<?php

declare(strict_types=1);

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'workplace_id' => $this->workplace_id,
            'workplace' => new WorkplaceResource($this->whenLoaded('workplace')),
            'event_type' => $this->event_type,
            'event_time' => $this->event_time->toIso8601String(),
            'method' => $this->method,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'accuracy' => $this->accuracy,
            ],
            'notes' => $this->notes,
            'pair_event_id' => $this->pair_event_id,
            'duration_minutes' => $this->when($this->pair_event_id !== null, fn () => $this->getDurationMinutes()),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
