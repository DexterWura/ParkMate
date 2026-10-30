<?php

namespace App\Domains\Parking\Controllers;

use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Parking\Services\OccupancyBoard;
use App\Domains\Parking\Services\ParkingCatalog;
use App\Domains\Parking\Services\PlaceSearch;
use App\Domains\Parking\Services\RouteTraffic;
use App\Domains\Parking\Services\SpotPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MobileSpotController extends Controller
{
    public function __construct(
        private ParkingCatalog $catalog,
        private SpotPresenter $presenter,
        private OccupancyBoard $occupancy,
        private PlaceSearch $search,
        private RouteTraffic $traffic,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $swLat = $request->query('swLat');
        $swLng = $request->query('swLng');
        $neLat = $request->query('neLat');
        $neLng = $request->query('neLng');
        $hours = (float) ($request->query('hours', 1));

        if ($swLat === null || $swLng === null || $neLat === null || $neLng === null) {
            return response()->json(['error' => 'swLat, swLng, neLat, neLng are required'], 400);
        }

        $spots = $this->catalog->spotsInViewport((float) $swLat, (float) $swLng, (float) $neLat, (float) $neLng)
            ->map(fn (ParkingSpot $spot) => $this->presenter->summary($spot, $hours))
            ->values();

        return response()->json([
            'spots' => $spots,
            'attribution' => '© OpenStreetMap contributors',
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $hours = (float) ($request->query('hours', 1));
        $spot = ParkingSpot::query()->with('latestActiveReport')->find($id);
        if ($spot === null || ! $spot->is_active) {
            return response()->json(['error' => 'Spot not found'], 404);
        }

        return response()->json($this->presenter->detail($spot, $hours));
    }

    public function report(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:FREE,TAKEN',
            'deviceId' => 'required|string|max:100',
        ]);

        try {
            $this->occupancy->report($id, $data['status'], $data['deviceId'], $request->user()?->id);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $spot = ParkingSpot::query()->with('latestActiveReport')->findOrFail($id);

        return response()->json($this->presenter->detail($spot, 1.0));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'deviceId' => 'required|string|max:100',
        ]);

        $spot = ParkingSpot::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'] ?? null,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'access_type' => 'yes',
            'source' => 'USER',
            'is_active' => true,
        ]);

        return response()->json($this->presenter->detail($spot, 1.0), 201);
    }

    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');

        return response()->json([
            'results' => $this->search->search($q),
        ]);
    }

    public function route(Request $request): JsonResponse
    {
        $data = $request->validate([
            'originLat' => 'required|numeric',
            'originLng' => 'required|numeric',
            'destinationSpotId' => 'required|string',
        ]);

        return response()->json(
            $this->traffic->route((float) $data['originLat'], (float) $data['originLng'], $data['destinationSpotId'])
        );
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'database' => config('database.default'),
        ]);
    }
}
