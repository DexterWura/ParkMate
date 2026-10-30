<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Models\FeeRule;
use App\Domains\Parking\Models\ParkingSpot;

class FeeEstimator
{
    public function preview(ParkingSpot $spot, ?float $hours = 1.0): array
    {
        $osm = $this->parseOsmCharge($spot->osm_charge);
        if ($osm !== null) {
            return [
                'hourlyRate' => $osm,
                'estimate' => $hours !== null ? [
                    'amount' => $osm['amount'] * $hours,
                    'currency' => $osm['currency'],
                ] : null,
                'hours' => $hours,
                'sourceNote' => 'OpenStreetMap charge tag',
                'kind' => $this->kindFromParkingType($spot->parking_type),
            ];
        }

        $rule = FeeRule::query()
            ->where('parking_spot_id', $spot->id)
            ->first();

        if ($rule === null && $spot->city) {
            $kind = $this->kindFromParkingType($spot->parking_type) ?? 'LOT';
            $rule = FeeRule::query()
                ->where('city', $spot->city)
                ->whereNull('parking_spot_id')
                ->where('kind', $kind)
                ->first()
                ?? FeeRule::query()
                    ->where('city', $spot->city)
                    ->whereNull('parking_spot_id')
                    ->first();
        }

        if ($rule !== null) {
            return [
                'hourlyRate' => [
                    'amount' => (float) $rule->amount,
                    'currency' => $rule->currency,
                ],
                'estimate' => $hours !== null ? [
                    'amount' => (float) $rule->amount * $hours,
                    'currency' => $rule->currency,
                ] : null,
                'hours' => $hours,
                'sourceNote' => $rule->source_note,
                'kind' => $rule->kind,
            ];
        }

        return [
            'hourlyRate' => null,
            'estimate' => null,
            'hours' => $hours,
            'sourceNote' => null,
            'kind' => $this->kindFromParkingType($spot->parking_type),
        ];
    }

    private function kindFromParkingType(?string $parkingType): ?string
    {
        return match (strtolower((string) $parkingType)) {
            'multi-storey', 'underground', 'rooftop' => 'PARKADE',
            'lane', 'street_side', 'on_kerb', 'half_on_kerb' => 'ON_STREET',
            '' => null,
            default => 'LOT',
        };
    }

    private function parseOsmCharge(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        if (! preg_match('/([0-9]+(?:\.[0-9]+)?)\s*([A-Za-z]{3})?/i', $raw, $m)) {
            return null;
        }

        return [
            'amount' => (float) $m[1],
            'currency' => strtoupper($m[2] ?: 'USD'),
        ];
    }
}
