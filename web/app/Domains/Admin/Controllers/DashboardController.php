<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Parking\Models\OccupancyReport;
use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\User\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'spotCount' => ParkingSpot::query()->where('is_active', true)->count(),
            'userCount' => User::query()->count(),
            'reportCount' => OccupancyReport::query()->where('expires_at', '>', now())->count(),
            'recentSpots' => ParkingSpot::query()->latest()->limit(8)->get(),
            'recentReports' => OccupancyReport::query()->with('spot')->latest()->limit(8)->get(),
        ]);
    }
}
