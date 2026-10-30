<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Models\ParkingSpot;

class SpotPresenter
{
    public function __construct(private FeeEstimator $fees) {}

    public function summary(ParkingSpot $spot, ?float $hours = 1.0): array
    {
        $report = $spot->relationLoaded('latestActiveReport')
            ? $spot->latestActiveReport
            : $spot->latestActiveReport()->first();

        return [
            'id' => $spot->id,
            'name' => $spot->name,
            'lat' => $spot->lat,
            'lng' => $spot->lng,
            'status' => $report?->status,
            'statusUpdatedAt' => $report?->created_at?->toIso8601String(),
            'fee' => $this->fees->preview($spot, $hours),
            'parkingType' => $spot->parking_type,
            'access' => $spot->access_type,
            'capacity' => $spot->capacity,
        ];
    }

    public function detail(ParkingSpot $spot, ?float $hours = 1.0): array
    {
        $base = $this->summary($spot, $hours);

        return array_merge($base, [
            'osmId' => $spot->osm_id,
            'city' => $spot->city,
            'lastReportAt' => $base['statusUpdatedAt'],
            'attribution' => '© OpenStreetMap contributors',
        ]);
    }
}
