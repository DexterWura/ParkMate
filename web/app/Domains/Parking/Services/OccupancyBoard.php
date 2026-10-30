<?php

namespace App\Domains\Parking\Services;

use App\Domains\Parking\Models\OccupancyReport;
use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Settings\Models\AppSetting;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OccupancyBoard
{
    public function report(string $spotId, string $status, string $deviceId, ?int $userId = null): OccupancyReport
    {
        $spot = ParkingSpot::query()->find($spotId);
        if ($spot === null || ! $spot->is_active) {
            throw new NotFoundHttpException('Spot not found');
        }

        $since = now()->subHour();
        $count = OccupancyReport::query()
            ->where('device_id', $deviceId)
            ->where('created_at', '>', $since)
            ->count();

        if ($count >= 30) {
            throw new HttpException(429, 'Too many reports from this device');
        }

        $ttl = (int) (AppSetting::getValue('report_ttl_minutes', '25') ?: 25);

        return OccupancyReport::query()->create([
            'id' => (string) Str::uuid(),
            'parking_spot_id' => $spotId,
            'status' => strtoupper($status),
            'device_id' => $deviceId,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes($ttl),
        ]);
    }
}
