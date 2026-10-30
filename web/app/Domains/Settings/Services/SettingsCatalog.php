<?php

namespace App\Domains\Settings\Services;

use App\Domains\Settings\Models\AppSetting;

class SettingsCatalog
{
    /**
     * @return list<array{key:string,label:string,group:string,is_secret:bool,value?:string|null,help?:string}>
     */
    public function definitions(): array
    {
        return [
            ['key' => 'mapbox_token', 'label' => 'Mapbox Access Token', 'group' => 'apis', 'is_secret' => true, 'help' => 'Used for driving-traffic routes and congestion.'],
            ['key' => 'tomtom_api_key', 'label' => 'TomTom API Key', 'group' => 'apis', 'is_secret' => true, 'help' => 'Fallback routing when Mapbox is unavailable.'],
            ['key' => 'openrouteservice_api_key', 'label' => 'OpenRouteService API Key', 'group' => 'apis', 'is_secret' => true, 'help' => 'Optional third routing provider (no live congestion).'],
            ['key' => 'routing_provider', 'label' => 'Routing provider', 'group' => 'apis', 'is_secret' => false, 'value' => 'auto', 'help' => 'auto | mapbox | tomtom | openrouteservice'],
            ['key' => 'overpass_url', 'label' => 'Overpass API URL', 'group' => 'apis', 'is_secret' => false, 'value' => 'https://overpass-api.de/api/interpreter'],
            ['key' => 'nominatim_url', 'label' => 'Nominatim URL', 'group' => 'apis', 'is_secret' => false, 'value' => 'https://nominatim.openstreetmap.org'],
            ['key' => 'map_style_url', 'label' => 'MapLibre style URL', 'group' => 'map', 'is_secret' => false, 'value' => 'https://tiles.openfreemap.org/styles/liberty', 'help' => 'Mobile map style (MapLibre JSON style URL).'],
            ['key' => 'default_map_lat', 'label' => 'Default map latitude', 'group' => 'map', 'is_secret' => false, 'value' => '-17.8292'],
            ['key' => 'default_map_lng', 'label' => 'Default map longitude', 'group' => 'map', 'is_secret' => false, 'value' => '31.0522'],
            ['key' => 'default_map_zoom', 'label' => 'Default map zoom', 'group' => 'map', 'is_secret' => false, 'value' => '14'],
            ['key' => 'report_ttl_minutes', 'label' => 'Occupancy report TTL (minutes)', 'group' => 'parking', 'is_secret' => false, 'value' => '25'],
            ['key' => 'default_parking_hours', 'label' => 'Default parking hours', 'group' => 'parking', 'is_secret' => false, 'value' => '1'],
            ['key' => 'nearby_radius_meters', 'label' => 'Nearby search radius (meters)', 'group' => 'parking', 'is_secret' => false, 'value' => '800'],
            ['key' => 'max_spots_per_request', 'label' => 'Max spots per map request', 'group' => 'parking', 'is_secret' => false, 'value' => '250'],
            ['key' => 'enable_user_spots', 'label' => 'Allow user-submitted spots', 'group' => 'features', 'is_secret' => false, 'value' => '1', 'help' => '1 = on, 0 = off'],
            ['key' => 'enable_routing', 'label' => 'Enable routing / Go to bay', 'group' => 'features', 'is_secret' => false, 'value' => '1'],
            ['key' => 'enable_nominatim_search', 'label' => 'Enable Nominatim place search', 'group' => 'features', 'is_secret' => false, 'value' => '1'],
            ['key' => 'enable_osm_ingest', 'label' => 'Enable OSM Overpass ingest', 'group' => 'features', 'is_secret' => false, 'value' => '1'],
            ['key' => 'enable_occupancy_reports', 'label' => 'Enable occupancy reports', 'group' => 'features', 'is_secret' => false, 'value' => '1'],
            ['key' => 'support_email', 'label' => 'Support / contact email', 'group' => 'app', 'is_secret' => false, 'value' => 'admin@openlot.companyop.com'],
            ['key' => 'app_display_name', 'label' => 'Mobile display name', 'group' => 'app', 'is_secret' => false, 'value' => 'OpenLot'],
        ];
    }

    public function ensureDefaults(): void
    {
        foreach ($this->definitions() as $setting) {
            AppSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'] ?? null,
                    'label' => $setting['label'],
                    'group' => $setting['group'],
                    'is_secret' => $setting['is_secret'],
                ]
            );
        }
    }

    public function helpFor(string $key): ?string
    {
        foreach ($this->definitions() as $setting) {
            if ($setting['key'] === $key) {
                return $setting['help'] ?? null;
            }
        }

        return null;
    }

    public function bool(string $key, bool $default = true): bool
    {
        $raw = AppSetting::getValue($key, $default ? '1' : '0');

        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    public function mobileConfig(): array
    {
        $mapbox = filled(AppSetting::getValue('mapbox_token'));
        $tomtom = filled(AppSetting::getValue('tomtom_api_key'));
        $ors = filled(AppSetting::getValue('openrouteservice_api_key'));

        return [
            'appName' => AppSetting::getValue('app_display_name', 'OpenLot') ?: 'OpenLot',
            'supportEmail' => AppSetting::getValue('support_email'),
            'defaultMapLat' => (float) (AppSetting::getValue('default_map_lat', '-17.8292') ?: -17.8292),
            'defaultMapLng' => (float) (AppSetting::getValue('default_map_lng', '31.0522') ?: 31.0522),
            'defaultMapZoom' => (float) (AppSetting::getValue('default_map_zoom', '14') ?: 14),
            'mapStyleUrl' => AppSetting::getValue('map_style_url', 'https://tiles.openfreemap.org/styles/liberty'),
            'reportTtlMinutes' => (int) (AppSetting::getValue('report_ttl_minutes', '25') ?: 25),
            'defaultParkingHours' => (float) (AppSetting::getValue('default_parking_hours', '1') ?: 1),
            'nearbyRadiusMeters' => (int) (AppSetting::getValue('nearby_radius_meters', '800') ?: 800),
            'maxSpotsPerRequest' => (int) (AppSetting::getValue('max_spots_per_request', '250') ?: 250),
            'attribution' => '© OpenStreetMap contributors',
            'features' => [
                'userSpots' => $this->bool('enable_user_spots'),
                'routing' => $this->bool('enable_routing'),
                'nominatimSearch' => $this->bool('enable_nominatim_search'),
                'osmIngest' => $this->bool('enable_osm_ingest'),
                'occupancyReports' => $this->bool('enable_occupancy_reports'),
            ],
            'providers' => [
                'routing' => AppSetting::getValue('routing_provider', 'auto') ?: 'auto',
                'mapboxConfigured' => $mapbox,
                'tomtomConfigured' => $tomtom,
                'openRouteServiceConfigured' => $ors,
                'routingAvailable' => $mapbox || $tomtom || $ors,
            ],
        ];
    }
}
