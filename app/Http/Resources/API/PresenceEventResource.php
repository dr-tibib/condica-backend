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
            'employee_id' => $this->employee_id,
            'workplace_id' => $this->workplace_id,
            'workplace' => new WorkplaceResource($this->whenLoaded('workplace')),
            'type' => $this->type,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at ? $this->end_at->toIso8601String() : null,
            'start_method' => $this->start_method,
            'end_method' => $this->end_method,
            'start_location' => [
                'latitude' => $this->start_latitude,
                'longitude' => $this->start_longitude,
                'accuracy' => $this->start_accuracy,
            ],
            'end_location' => [
                'latitude' => $this->end_latitude,
                'longitude' => $this->end_longitude,
                'accuracy' => $this->end_accuracy,
            ],
            'notes' => $this->notes,
            'linkable_id' => $this->linkable_id,
            'linkable_type' => $this->linkable_type,
            'duration_minutes' => $this->getDurationMinutes(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
