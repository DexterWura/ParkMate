<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Settings\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteTraffic
{
    public function route(float $originLat, float $originLng, string $destinationSpotId): array
    {
        $token = AppSetting::getValue('mapbox_token');
        if (! $token) {
            return [
                'available' => false,
                'durationSeconds' => null,
                'distanceMeters' => null,
                'geometry' => [],
                'congestion' => [],
                'message' => 'Traffic routing is not configured. Set Mapbox token in admin settings.',
            ];
        }

        $spot = ParkingSpot::query()->find($destinationSpotId);
        if ($spot === null) {
            return [
                'available' => false,
                'message' => 'Destination spot not found',
                'geometry' => [],
                'congestion' => [],
            ];
        }

        $url = sprintf(
            'https://api.mapbox.com/directions/v5/mapbox/driving-traffic/%s,%s;%s,%s',
            $originLng,
            $originLat,
            $spot->lng,
            $spot->lat
        );

        try {
            $response = Http::timeout(25)->get($url, [
                'geometries' => 'geojson',
                'overview' => 'full',
                'annotations' => 'congestion',
                'access_token' => $token,
            ]);

            if (! $response->successful()) {
                return [
                    'available' => false,
                    'message' => 'Traffic provider returned '.$response->status(),
                    'geometry' => [],
                    'congestion' => [],
                ];
            }

            $route = $response->json('routes.0');
            if (! $route) {
                return [
                    'available' => false,
                    'message' => 'No route found',
                    'geometry' => [],
                    'congestion' => [],
                ];
            }

            $geometry = collect($route['geometry']['coordinates'] ?? [])
                ->map(fn ($pair) => ['lat' => $pair[1], 'lng' => $pair[0]])
                ->all();

            $values = $route['legs'][0]['annotation']['congestion'] ?? [];
            $segments = [];
            $i = 0;
            while ($i < count($values)) {
                $level = $this->mapCongestion($values[$i] ?? null);
                $end = $i;
                while ($end + 1 < count($values) && $this->mapCongestion($values[$end + 1] ?? null) === $level) {
                    $end++;
                }
                $segments[] = [
                    'fromIndex' => $i,
                    'toIndex' => $end + 1,
                    'level' => $level,
                ];
                $i = $end + 1;
            }

            return [
                'available' => true,
                'durationSeconds' => $route['duration'] ?? null,
                'distanceMeters' => $route['distance'] ?? null,
                'geometry' => $geometry,
                'congestion' => $segments,
                'message' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Mapbox route failed', ['error' => $e->getMessage()]);

            return [
                'available' => false,
                'message' => 'Traffic routing failed',
                'geometry' => [],
                'congestion' => [],
            ];
        }
    }

    private function mapCongestion(?string $raw): string
    {
        return match (strtolower((string) $raw)) {
            'low' => 'LOW',
            'moderate' => 'MODERATE',
            'heavy' => 'HEAVY',
            'severe' => 'SEVERE',
            default => 'UNKNOWN',
        };
    }
}
