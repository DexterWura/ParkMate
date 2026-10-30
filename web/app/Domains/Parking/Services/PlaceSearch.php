<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Settings\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaceSearch
{
    public function search(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        $local = ParkingSpot::query()
            ->where('is_active', true)
            ->where('name', 'like', '%'.$q.'%')
            ->limit(10)
            ->get()
            ->map(fn (ParkingSpot $spot) => [
                'id' => 'spot:'.$spot->id,
                'title' => $spot->name ?: 'Parking spot',
                'subtitle' => collect([$spot->city, $spot->parking_type])->filter()->implode(' · ') ?: null,
                'lat' => $spot->lat,
                'lng' => $spot->lng,
                'kind' => 'spot',
                'spotId' => $spot->id,
            ])
            ->all();

        $cacheKey = 'nominatim:'.mb_strtolower($q);
        $remote = Cache::remember($cacheKey, now()->addDay(), function () use ($q) {
            return $this->fetchNominatim($q);
        });

        $merged = [];
        foreach (array_merge($local, $remote) as $item) {
            $key = $item['lat'].','.$item['lng'].','.mb_strtolower($item['title']);
            $merged[$key] ??= $item;
        }

        return array_values($merged);
    }

    private function fetchNominatim(string $query): array
    {
        $base = rtrim(AppSetting::getValue('nominatim_url', 'https://nominatim.openstreetmap.org') ?? '', '/');

        try {
            // Respect Nominatim 1 req/s guidance with a short lock.
            $lock = Cache::lock('nominatim-rate', 2);
            $lock->block(3);

            $response = Http::withHeaders([
                'User-Agent' => 'ParkMate/1.0 (parking finder; contact=admin@parkmate.local)',
            ])->timeout(20)->get($base.'/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 8,
            ]);

            $lock->release();

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json() ?? [])->map(function (array $row) {
                return [
                    'id' => 'place:'.($row['osm_id'] ?? $row['place_id'] ?? uniqid()),
                    'title' => explode(',', $row['display_name'] ?? 'Place')[0],
                    'subtitle' => $row['display_name'] ?? null,
                    'lat' => (float) $row['lat'],
                    'lng' => (float) $row['lon'],
                    'kind' => 'place',
                    'spotId' => null,
                ];
            })->all();
        } catch (\Throwable $e) {
            Log::warning('Nominatim failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
