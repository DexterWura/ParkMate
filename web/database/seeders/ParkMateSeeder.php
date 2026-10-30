<?php

namespace Database\Seeders;

use App\Domains\Parking\Models\FeeRule;
use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Settings\Models\AppSetting;
use App\Domains\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParkMateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@parkmate.local'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'ParkMate Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $admin->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $spots = [
            [
                'id' => 'seed-samora-machel',
                'name' => 'Samora Machel Parkade',
                'lat' => -17.8292,
                'lng' => 31.0522,
                'parking_type' => 'multi-storey',
                'kind' => 'PARKADE',
            ],
            [
                'id' => 'seed-julius-nyerere',
                'name' => 'Julius Nyerere Parkade',
                'lat' => -17.8310,
                'lng' => 31.0475,
                'parking_type' => 'multi-storey',
                'kind' => 'PARKADE',
            ],
            [
                'id' => 'seed-simon-muzenda',
                'name' => 'Simon Muzenda Parking Lot',
                'lat' => -17.8275,
                'lng' => 31.0488,
                'parking_type' => 'surface',
                'kind' => 'LOT',
            ],
            [
                'id' => 'seed-rufaro',
                'name' => 'Rufaro Stadium Parking Lot',
                'lat' => -17.8635,
                'lng' => 31.0345,
                'parking_type' => 'surface',
                'kind' => 'LOT',
            ],
            [
                'id' => 'seed-harare-cbd-street',
                'name' => 'Harare CBD On-Street Sample',
                'lat' => -17.8277,
                'lng' => 31.0530,
                'parking_type' => 'lane',
                'kind' => 'ON_STREET',
            ],
        ];

        foreach ($spots as $spot) {
            ParkingSpot::query()->updateOrCreate(
                ['id' => $spot['id']],
                [
                    'name' => $spot['name'],
                    'lat' => $spot['lat'],
                    'lng' => $spot['lng'],
                    'parking_type' => $spot['parking_type'],
                    'access_type' => 'yes',
                    'osm_fee' => 'yes',
                    'city' => 'Harare',
                    'source' => 'CITY_PARKING',
                    'is_active' => true,
                ]
            );

            FeeRule::query()->updateOrCreate(
                ['id' => 'fee-'.$spot['id']],
                [
                    'city' => 'Harare',
                    'kind' => $spot['kind'],
                    'amount' => 1.00,
                    'currency' => 'USD',
                    'unit' => 'HOUR',
                    'source_note' => 'City Parking published rate (US$1/hour). SI 41 of 2026 gazetted a US$0.50 cap that was not yet what drivers were being charged.',
                    'effective_from' => '2024-01-01',
                    'parking_spot_id' => $spot['id'],
                ]
            );
        }

        FeeRule::query()->updateOrCreate(
            ['id' => 'fee-harare-on-street'],
            [
                'city' => 'Harare',
                'kind' => 'ON_STREET',
                'amount' => 1.00,
                'currency' => 'USD',
                'unit' => 'HOUR',
                'source_note' => 'City Parking published rate (US$1/hour).',
                'effective_from' => '2024-01-01',
                'parking_spot_id' => null,
            ]
        );

        FeeRule::query()->updateOrCreate(
            ['id' => 'fee-bulawayo-on-street'],
            [
                'city' => 'Bulawayo',
                'kind' => 'ON_STREET',
                'amount' => 1.00,
                'currency' => 'USD',
                'unit' => 'HOUR',
                'source_note' => 'Bulawayo published on-street rate (US$1/hour).',
                'effective_from' => '2024-01-01',
                'parking_spot_id' => null,
            ]
        );

        $settings = [
            ['key' => 'mapbox_token', 'label' => 'Mapbox Access Token', 'group' => 'apis', 'is_secret' => true],
            ['key' => 'tomtom_api_key', 'label' => 'TomTom API Key', 'group' => 'apis', 'is_secret' => true],
            ['key' => 'overpass_url', 'label' => 'Overpass API URL', 'group' => 'apis', 'is_secret' => false, 'value' => 'https://overpass-api.de/api/interpreter'],
            ['key' => 'nominatim_url', 'label' => 'Nominatim URL', 'group' => 'apis', 'is_secret' => false, 'value' => 'https://nominatim.openstreetmap.org'],
            ['key' => 'report_ttl_minutes', 'label' => 'Occupancy report TTL (minutes)', 'group' => 'parking', 'is_secret' => false, 'value' => '25'],
            ['key' => 'default_map_lat', 'label' => 'Default map latitude', 'group' => 'map', 'is_secret' => false, 'value' => '-17.8292'],
            ['key' => 'default_map_lng', 'label' => 'Default map longitude', 'group' => 'map', 'is_secret' => false, 'value' => '31.0522'],
        ];

        foreach ($settings as $setting) {
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
}
