<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Settings\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParkingCatalog
{
    /** @var array<int, array{name:string,south:float,west:float,north:float,east:float}> */
    private array $zimCities = [
        ['name' => 'Harare', 'south' => -17.95, 'west' => 30.90, 'north' => -17.70, 'east' => 31.20],
        ['name' => 'Bulawayo', 'south' => -20.25, 'west' => 28.45, 'north' => -20.05, 'east' => 28.75],
        ['name' => 'Mutare', 'south' => -19.00, 'west' => 32.55, 'north' => -18.90, 'east' => 32.75],
        ['name' => 'Gweru', 'south' => -19.50, 'west' => 29.70, 'north' => -19.40, 'east' => 29.90],
    ];

    public function ensureZimbabwePrefetch(): void
    {
        foreach ($this->zimCities as $city) {
            $this->refreshBbox($city['south'], $city['west'], $city['north'], $city['east'], $city['name'], false);
        }
    }

    public function spotsInViewport(float $swLat, float $swLng, float $neLat, float $neLng)
    {
        $this->refreshBbox(
            min($swLat, $neLat),
            min($swLng, $neLng),
            max($swLat, $neLat),
            max($swLng, $neLng),
            null,
            false
        );

        return ParkingSpot::query()
            ->with('latestActiveReport')
            ->where('is_active', true)
            ->whereBetween('lat', [min($swLat, $neLat), max($swLat, $neLat)])
            ->whereBetween('lng', [min($swLng, $neLng), max($swLng, $neLng)])
            ->orderBy('name')
            ->get();
    }

    public function refreshBbox(float $south, float $west, float $north, float $east, ?string $cityHint, bool $force): void
    {
        $key = sprintf('bbox:%.3f:%.3f:%.3f:%.3f', $south, $west, $north, $east);
        $fetched = DB::table('ingest_cache')->where('cache_key', $key)->value('fetched_at');

        if (! $force && $fetched && now()->parse($fetched)->gt(now()->subDay())) {
            return;
        }

        try {
            $this->ingestOverpass($south, $west, $north, $east, $cityHint);
            DB::table('ingest_cache')->upsert(
                ['cache_key' => $key, 'fetched_at' => now()],
                ['cache_key'],
                ['fetched_at']
            );
        } catch (\Throwable $e) {
            Log::warning('Overpass ingest failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    private function ingestOverpass(float $south, float $west, float $north, float $east, ?string $cityHint): void
    {
        $url = AppSetting::getValue('overpass_url', 'https://overpass-api.de/api/interpreter');
        $query = <<<QL
[out:json][timeout:60];
(
  node["amenity"="parking"]($south,$west,$north,$east);
  way["amenity"="parking"]($south,$west,$north,$east);
  relation["amenity"="parking"]($south,$west,$north,$east);
);
out center tags;
QL;

        $response = Http::asForm()
            ->timeout(70)
            ->post($url, ['data' => $query]);

        if (! $response->successful()) {
            throw new \RuntimeException('Overpass HTTP '.$response->status());
        }

        $elements = $response->json('elements') ?? [];
        foreach ($elements as $el) {
            $type = $el['type'] ?? null;
            $id = $el['id'] ?? null;
            if (! $type || ! $id) {
                continue;
            }

            $lat = $type === 'node' ? ($el['lat'] ?? null) : ($el['center']['lat'] ?? null);
            $lon = $type === 'node' ? ($el['lon'] ?? null) : ($el['center']['lon'] ?? null);
            if ($lat === null || $lon === null) {
                continue;
            }

            $tags = $el['tags'] ?? [];
            $osmId = $type.'/'.$id;

            $existing = ParkingSpot::query()->where('osm_id', $osmId)->first();
            $payload = [
                'name' => $tags['name'] ?? null,
                'lat' => $lat,
                'lng' => $lon,
                'parking_type' => $tags['parking'] ?? null,
                'access_type' => $tags['access'] ?? null,
                'capacity' => isset($tags['capacity']) && is_numeric($tags['capacity']) ? (int) $tags['capacity'] : null,
                'osm_fee' => $tags['fee'] ?? null,
                'osm_charge' => $tags['charge'] ?? null,
                'city' => $cityHint ?? $existing?->city,
                'source' => 'OSM',
                'is_active' => true,
            ];

            if ($existing) {
                $existing->fill($payload)->save();
            } else {
                ParkingSpot::query()->create(array_merge($payload, [
                    'id' => (string) Str::uuid(),
                    'osm_id' => $osmId,
                ]));
            }
        }
    }
}
