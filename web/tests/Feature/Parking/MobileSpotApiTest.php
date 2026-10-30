<?php

namespace Tests\Feature\Parking;

use App\Domains\Parking\Models\ParkingSpot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileSpotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_ok_when_database_is_up(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'database']);
    }

    public function test_config_returns_map_defaults(): void
    {
        $this->getJson('/api/v1/config')
            ->assertOk()
            ->assertJsonStructure([
                'defaultMapLat',
                'defaultMapLng',
                'reportTtlMinutes',
                'attribution',
                'features' => ['userSpots', 'routing'],
                'providers' => ['routing', 'routingAvailable'],
                'mapStyleUrl',
                'nearbyRadiusMeters',
            ]);
    }

    public function test_nearby_returns_spots_with_distance(): void
    {
        ParkingSpot::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Near Bay',
            'lat' => -17.8292,
            'lng' => 31.0522,
            'source' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/spots/nearby?lat=-17.8292&lng=31.0522&radiusMeters=500')
            ->assertOk()
            ->assertJsonPath('spots.0.name', 'Near Bay')
            ->assertJsonStructure(['spots' => [['distanceMeters']], 'radiusMeters']);
    }

    public function test_providers_endpoint(): void
    {
        $this->getJson('/api/v1/providers')
            ->assertOk()
            ->assertJsonStructure(['providers', 'features']);
    }

    public function test_spots_require_bbox(): void
    {
        $this->getJson('/api/v1/spots')->assertStatus(422);
    }

    public function test_spots_in_viewport_returns_seeded_style_spot(): void
    {
        ParkingSpot::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Bay',
            'lat' => -17.8292,
            'lng' => 31.0522,
            'source' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/spots?swLat=-17.84&swLng=31.04&neLat=-17.82&neLng=31.06')
            ->assertOk()
            ->assertJsonPath('spots.0.name', 'Test Bay')
            ->assertJsonStructure(['spots', 'attribution']);
    }

    public function test_report_and_create_spot(): void
    {
        $spot = ParkingSpot::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Report Bay',
            'lat' => -17.83,
            'lng' => 31.05,
            'source' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->postJson("/api/v1/spots/{$spot->id}/reports", [
            'status' => 'FREE',
            'deviceId' => 'test-device',
            'hours' => 2,
        ])->assertOk()->assertJsonPath('status', 'FREE');

        $this->postJson('/api/v1/spots', [
            'name' => 'User Bay',
            'lat' => -17.831,
            'lng' => 31.051,
            'deviceId' => 'test-device',
        ])->assertCreated()->assertJsonPath('name', 'User Bay');
    }
}
