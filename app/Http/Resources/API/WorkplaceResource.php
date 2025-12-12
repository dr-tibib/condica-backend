<?php

declare(strict_types=1);

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkplaceResource extends JsonResource
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
            'name' => $this->name,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'radius' => $this->radius,
            'timezone' => $this->timezone,
            'wifi_ssid' => $this->wifi_ssid,
            'is_active' => $this->is_active,
            'distance' => $this->when(isset($this->distance), fn () => round($this->distance, 2)),
        ];
    }
}
