<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_places.key');
    }

    /**
     * Search for a place by name, prioritizing Romania and Brasov.
     */
    public function searchPlace(string $name): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key is missing.');
            return null;
        }

        $query = "{$name}, Romania";
        if (!str_contains(strtolower($name), 'brasov')) {
            $query .= ", Brasov";
        }

        try {
            // Using Places API (New) Text Search
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.photos,places.rating',
            ])->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $query,
                'languageCode' => 'ro',
                'regionCode' => 'ro',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Google Places API (New) success for {$query}.");
                
                if (!empty($data['places'])) {
                    $result = $data['places'][0];
                    return [
                        'google_place_id' => $result['id'],
                        'name' => $result['displayName']['text'] ?? $name,
                        'address' => $result['formattedAddress'] ?? null,
                        'latitude' => $result['location']['latitude'] ?? null,
                        'longitude' => $result['location']['longitude'] ?? null,
                        'photo_reference' => $result['photos'][0]['name'] ?? null,
                        'full_result' => $result,
                    ];
                } else {
                    Log::warning("No results found for query: {$query}. Response: " . json_encode($data));
                }
            } else {
                Log::error('Google Places API error: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Google Places API exception: ' . $e->getMessage());
        }

        return null;
    }
}
