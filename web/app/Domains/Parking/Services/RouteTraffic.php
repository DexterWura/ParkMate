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
        $spot = ParkingSpot::query()->find($destinationSpotId);
        if ($spot === null) {
            return $this->unavailable('Destination spot not found');
        }

        $preferred = strtolower((string) (AppSetting::getValue('routing_provider', 'auto') ?: 'auto'));
        $providers = match ($preferred) {
            'mapbox' => ['mapbox'],
            'tomtom' => ['tomtom'],
            'openrouteservice' => ['openrouteservice'],
            default => ['mapbox', 'tomtom', 'openrouteservice'],
        };

        $lastMessage = 'Traffic routing is not configured. Add a Mapbox, TomTom, or OpenRouteService key in admin settings.';

        foreach ($providers as $provider) {
            $result = match ($provider) {
                'mapbox' => $this->routeMapbox($originLat, $originLng, $spot),
                'tomtom' => $this->routeTomTom($originLat, $originLng, $spot),
                'openrouteservice' => $this->routeOpenRouteService($originLat, $originLng, $spot),
                default => null,
            };

            if ($result === null) {
                continue;
            }

            if ($result['available'] ?? false) {
                $result['provider'] = $provider;

                return $result;
            }

            $lastMessage = $result['message'] ?? $lastMessage;
        }

        return $this->unavailable($lastMessage);
    }

    private function routeMapbox(float $originLat, float $originLng, ParkingSpot $spot): ?array
    {
        $token = AppSetting::getValue('mapbox_token');
        if (! $token) {
            return null;
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
                return $this->unavailable('Mapbox returned '.$response->status());
            }

            $route = $response->json('routes.0');
            if (! $route) {
                return $this->unavailable('No Mapbox route found');
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

            return $this->unavailable('Mapbox routing failed');
        }
    }

    private function routeTomTom(float $originLat, float $originLng, ParkingSpot $spot): ?array
    {
        $key = AppSetting::getValue('tomtom_api_key');
        if (! $key) {
            return null;
        }

        $url = sprintf(
            'https://api.tomtom.com/routing/1/calculateRoute/%s,%s:%s,%s/json',
            $originLat,
            $originLng,
            $spot->lat,
            $spot->lng
        );

        try {
            $response = Http::timeout(25)->get($url, [
                'key' => $key,
                'traffic' => 'true',
                'travelMode' => 'car',
            ]);

            if (! $response->successful()) {
                return $this->unavailable('TomTom returned '.$response->status());
            }

            $route = $response->json('routes.0');
            if (! $route) {
                return $this->unavailable('No TomTom route found');
            }

            $points = collect($route['legs'][0]['points'] ?? [])
                ->map(fn ($p) => ['lat' => (float) $p['latitude'], 'lng' => (float) $p['longitude']])
                ->all();

            $summary = $route['summary'] ?? [];

            return [
                'available' => true,
                'durationSeconds' => isset($summary['travelTimeInSeconds']) ? (float) $summary['travelTimeInSeconds'] : null,
                'distanceMeters' => isset($summary['lengthInMeters']) ? (float) $summary['lengthInMeters'] : null,
                'geometry' => $points,
                'congestion' => [],
                'message' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('TomTom route failed', ['error' => $e->getMessage()]);

            return $this->unavailable('TomTom routing failed');
        }
    }

    private function routeOpenRouteService(float $originLat, float $originLng, ParkingSpot $spot): ?array
    {
        $key = AppSetting::getValue('openrouteservice_api_key');
        if (! $key) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $key,
                'Content-Type' => 'application/json',
            ])->timeout(25)->post('https://api.openrouteservice.org/v2/directions/driving-car/geojson', [
                'coordinates' => [
                    [$originLng, $originLat],
                    [$spot->lng, $spot->lat],
                ],
            ]);

            if (! $response->successful()) {
                return $this->unavailable('OpenRouteService returned '.$response->status());
            }

            $coords = $response->json('features.0.geometry.coordinates') ?? [];
            $summary = $response->json('features.0.properties.summary') ?? [];
            $geometry = collect($coords)
                ->map(fn ($pair) => ['lat' => $pair[1], 'lng' => $pair[0]])
                ->all();

            return [
                'available' => count($geometry) >= 2,
                'durationSeconds' => isset($summary['duration']) ? (float) $summary['duration'] : null,
                'distanceMeters' => isset($summary['distance']) ? (float) $summary['distance'] : null,
                'geometry' => $geometry,
                'congestion' => [],
                'message' => count($geometry) >= 2 ? null : 'No OpenRouteService route found',
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenRouteService route failed', ['error' => $e->getMessage()]);

            return $this->unavailable('OpenRouteService routing failed');
        }
    }

    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'durationSeconds' => null,
            'distanceMeters' => null,
            'geometry' => [],
            'congestion' => [],
            'message' => $message,
            'provider' => null,
        ];
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
