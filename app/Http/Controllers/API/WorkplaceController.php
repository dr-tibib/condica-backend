<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\WorkplaceResource;
use App\Models\Workplace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkplaceController extends Controller
{
    /**
     * List all active workplaces with optional distance calculation.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $workplaces = Workplace::where('is_active', true)->get();

        // Calculate distance if user location is provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $userLat = (float) $request->input('latitude');
            $userLon = (float) $request->input('longitude');

            $workplaces = $workplaces->map(function ($workplace) use ($userLat, $userLon) {
                $workplace->distance = $workplace->calculateDistance($userLat, $userLon);

                return $workplace;
            });

            // Sort by distance
            $workplaces = $workplaces->sortBy('distance')->values();
        }

        return WorkplaceResource::collection($workplaces);
    }
}
