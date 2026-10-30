<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Jobs\RefreshParkingBboxJob;
use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Settings\Models\AppSetting;
use App\Domains\Settings\Services\SettingsCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParkingCatalog
{
    public const MAX_SPOTS_PER_VIEWPORT = 250;

    public function __construct(private SettingsCatalog $settings) {}

    /** @var array<int, array{name:string,south:float,west:float,north:float,east:float}> */
    private array $zimCities = [
        ['name' => 'Harare', 'south' => -17.95, 'west' => 30.90, 'north' => -17.70, 'east' => 31.20],
        ['name' => 'Bulawayo', 'south' => -20.25, 'west' => 28.45, 'north' => -20.05, 'east' => 28.75],
        ['name' => 'Mutare', 'south' => -19.00, 'west' => 32.55, 'north' => -18.90, 'east' => 32.75],
        ['name' => 'Gweru', 'south' => -19.50, 'west' => 29.70, 'north' => -19.40, 'east' => 29.90],
    ];

    public function ensureZimbabwePrefetch(bool $async = true): void
    {
        foreach ($this->zimCities as $city) {
            if ($async) {
                RefreshParkingBboxJob::dispatch(
                    $city['south'],
                    $city['west'],
                    $city['north'],
                    $city['east'],
                    $city['name'],
                    false,
                )->afterResponse();
            } else {
                $this->refreshBbox($city['south'], $city['west'], $city['north'], $city['east'], $city['name'], false);
            }
        }
    }

    /**
     * @param  array{status?:string|null,parkingType?:string|null,access?:string|null}  $filters
     */
    public function spotsInViewport(float $swLat, float $swLng, float $neLat, float $neLng, array $filters = []): Collection
    {
        $south = min($swLat, $neLat);
        $west = min($swLng, $neLng);
        $north = max($swLat, $neLat);
        $east = max($swLng, $neLng);

        // Serve DB spots immediately; refresh OSM in the background when cache is stale.
        if ($this->settings->bool('enable_osm_ingest') && $this->bboxNeedsRefresh($south, $west, $north, $east)) {
            RefreshParkingBboxJob::dispatch($south, $west, $north, $east, null, false)->afterResponse();
        }

        $limit = max(10, min(500, (int) (AppSetting::getValue('max_spots_per_request', (string) self::MAX_SPOTS_PER_VIEWPORT) ?: self::MAX_SPOTS_PER_VIEWPORT)));

        $query = ParkingSpot::query()
            ->with('latestActiveReport')
            ->where('is_active', true)
            ->whereBetween('lat', [$south, $north])
            ->whereBetween('lng', [$west, $east]);

        $this->applySqlFilters($query, $filters);

        $spots = $query->orderBy('name')->limit($limit)->get();

        return $this->applyStatusFilter($spots, $filters['status'] ?? null);
    }

    /**
     * Approximate nearby search using a bounding box, then distance sort in PHP.
     *
     * @param  array{status?:string|null,parkingType?:string|null,access?:string|null}  $filters
     */
    public function spotsNearby(float $lat, float $lng, int $radiusMeters, int $limit = 40, array $filters = []): Collection
    {
        $radiusMeters = max(50, min(5000, $radiusMeters));
        $limit = max(1, min(100, $limit));
        $deltaLat = $radiusMeters / 111_320;
        $deltaLng = $radiusMeters / max(1.0, 111_320 * cos(deg2rad($lat)));

        $query = ParkingSpot::query()
            ->with('latestActiveReport')
            ->where('is_active', true)
            ->whereBetween('lat', [$lat - $deltaLat, $lat + $deltaLat])
            ->whereBetween('lng', [$lng - $deltaLng, $lng + $deltaLng]);

        $this->applySqlFilters($query, $filters);

        return $this->applyStatusFilter(
            $query->limit(250)->get(),
            $filters['status'] ?? null,
        )
            ->map(function (ParkingSpot $spot) use ($lat, $lng) {
                $spot->setAttribute('distance_meters', $this->haversineMeters($lat, $lng, (float) $spot->lat, (float) $spot->lng));

                return $spot;
            })
            ->filter(fn (ParkingSpot $spot) => (float) $spot->getAttribute('distance_meters') <= $radiusMeters)
            ->sortBy(fn (ParkingSpot $spot) => (float) $spot->getAttribute('distance_meters'))
            ->take($limit)
            ->values();
    }

    /**
     * @param  array{status?:string|null,parkingType?:string|null,access?:string|null}  $filters
     */
    private function applySqlFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['parkingType'])) {
            $query->where('parking_type', $filters['parkingType']);
        }
        if (! empty($filters['access'])) {
            $query->where('access_type', $filters['access']);
        }
    }

    private function applyStatusFilter(Collection $spots, ?string $status): Collection
    {
        if ($status === null || $status === '') {
            return $spots;
        }

        $wanted = strtoupper($status);

        return $spots
            ->filter(fn (ParkingSpot $spot) => strtoupper((string) $spot->latestActiveReport?->status) === $wanted)
            ->values();
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6_371_000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    public function bboxNeedsRefresh(float $south, float $west, float $north, float $east, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        $key = $this->cacheKey($south, $west, $north, $east);
        $fetched = DB::table('ingest_cache')->where('cache_key', $key)->value('fetched_at');

        return ! $fetched || now()->parse($fetched)->lte(now()->subDay());
    }

    public function refreshBbox(float $south, float $west, float $north, float $east, ?string $cityHint, bool $force): void
    {
        $key = $this->cacheKey($south, $west, $north, $east);
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

    private function cacheKey(float $south, float $west, float $north, float $east): string
    {
        return sprintf('bbox:%.3f:%.3f:%.3f:%.3f', $south, $west, $north, $east);
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
