<?php

namespace App\Domains\Parking\Controllers;

use App\Domains\Parking\Models\OccupancyReport;
use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Parking\Services\OccupancyBoard;
use App\Domains\Parking\Services\ParkingCatalog;
use App\Domains\Parking\Services\PlaceSearch;
use App\Domains\Parking\Services\RouteTraffic;
use App\Domains\Parking\Services\SpotPresenter;
use App\Domains\Settings\Services\SettingsCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
        private SettingsCatalog $settings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'swLat' => 'required|numeric|between:-90,90',
            'swLng' => 'required|numeric|between:-180,180',
            'neLat' => 'required|numeric|between:-90,90',
            'neLng' => 'required|numeric|between:-180,180',
            'hours' => 'nullable|numeric|min:0.5|max:24',
            'status' => 'nullable|in:FREE,TAKEN',
            'parkingType' => 'nullable|string|max:80',
            'access' => 'nullable|string|max:40',
        ]);

        $hours = (float) ($validated['hours'] ?? 1);
        $swLat = (float) $validated['swLat'];
        $swLng = (float) $validated['swLng'];
        $neLat = (float) $validated['neLat'];
        $neLng = (float) $validated['neLng'];

        $latSpan = abs($neLat - $swLat);
        $lngSpan = abs($neLng - $swLng);
        if ($latSpan > 2.5 || $lngSpan > 2.5) {
            return response()->json(['error' => 'Viewport too large; zoom in and try again'], 400);
        }

        $filters = [
            'status' => $validated['status'] ?? null,
            'parkingType' => $validated['parkingType'] ?? null,
            'access' => $validated['access'] ?? null,
        ];

        $spots = $this->catalog->spotsInViewport($swLat, $swLng, $neLat, $neLng, $filters)
            ->map(fn (ParkingSpot $spot) => $this->presenter->summary($spot, $hours))
            ->values();

        return response()->json([
            'spots' => $spots,
            'attribution' => '© OpenStreetMap contributors',
            'filters' => (object) array_filter($filters),
        ]);
    }

    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radiusMeters' => 'nullable|integer|min:50|max:5000',
            'limit' => 'nullable|integer|min:1|max:100',
            'hours' => 'nullable|numeric|min:0.5|max:24',
            'status' => 'nullable|in:FREE,TAKEN',
            'parkingType' => 'nullable|string|max:80',
            'access' => 'nullable|string|max:40',
        ]);

        $hours = (float) ($validated['hours'] ?? 1);
        $radius = (int) ($validated['radiusMeters'] ?? $this->settings->mobileConfig()['nearbyRadiusMeters']);
        $limit = (int) ($validated['limit'] ?? 40);
        $filters = [
            'status' => $validated['status'] ?? null,
            'parkingType' => $validated['parkingType'] ?? null,
            'access' => $validated['access'] ?? null,
        ];

        $spots = $this->catalog
            ->spotsNearby((float) $validated['lat'], (float) $validated['lng'], $radius, $limit, $filters)
            ->map(function (ParkingSpot $spot) use ($hours) {
                $summary = $this->presenter->summary($spot, $hours);
                $summary['distanceMeters'] = round((float) $spot->getAttribute('distance_meters'), 1);

                return $summary;
            })
            ->values();

        return response()->json([
            'spots' => $spots,
            'radiusMeters' => $radius,
            'attribution' => '© OpenStreetMap contributors',
            'filters' => (object) array_filter($filters),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'hours' => 'nullable|numeric|min:0.5|max:24',
        ]);
        $hours = (float) ($validated['hours'] ?? 1.0);

        $spot = ParkingSpot::query()->with('latestActiveReport')->find($id);
        if ($spot === null || ! $spot->is_active) {
            return response()->json(['error' => 'Spot not found'], 404);
        }

        return response()->json($this->presenter->detail($spot, $hours));
    }

    public function report(Request $request, string $id): JsonResponse
    {
        if (! $this->settings->bool('enable_occupancy_reports')) {
            return response()->json(['error' => 'Occupancy reports are disabled'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:FREE,TAKEN',
            'deviceId' => 'required|string|max:100',
        ]);

        $hours = (float) ($request->input('hours', 1));

        try {
            $this->occupancy->report($id, $data['status'], $data['deviceId'], $request->user()?->id);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $spot = ParkingSpot::query()->with('latestActiveReport')->findOrFail($id);

        return response()->json($this->presenter->detail($spot, max(0.5, min(24.0, $hours))));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->settings->bool('enable_user_spots')) {
            return response()->json(['error' => 'User-submitted spots are disabled'], 403);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'deviceId' => 'required|string|max:100',
            'hours' => 'nullable|numeric|min:0.5|max:24',
        ]);

        $since = now()->subDay();
        $createdToday = ParkingSpot::query()
            ->where('source', 'USER')
            ->where('created_at', '>', $since)
            ->count();

        $deviceCreates = OccupancyReport::query()
            ->where('device_id', $data['deviceId'])
            ->where('created_at', '>', $since)
            ->count();

        if ($deviceCreates >= 40 || $createdToday >= 200) {
            return response()->json(['error' => 'Too many spot submissions from this device'], 429);
        }

        $nearDuplicate = ParkingSpot::query()
            ->where('is_active', true)
            ->whereBetween('lat', [$data['lat'] - 0.0003, $data['lat'] + 0.0003])
            ->whereBetween('lng', [$data['lng'] - 0.0003, $data['lng'] + 0.0003])
            ->first();

        if ($nearDuplicate !== null) {
            return response()->json($this->presenter->detail($nearDuplicate, (float) ($data['hours'] ?? 1)), 200);
        }

        $spot = ParkingSpot::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'] ?? null,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'access_type' => 'yes',
            'source' => 'USER',
            'is_active' => true,
        ]);

        return response()->json($this->presenter->detail($spot, (float) ($data['hours'] ?? 1)), 201);
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
        if (! $this->settings->bool('enable_routing')) {
            return response()->json([
                'available' => false,
                'geometry' => [],
                'congestion' => [],
                'message' => 'Routing is disabled in admin settings',
                'provider' => null,
            ]);
        }

        $data = $request->validate([
            'originLat' => 'required|numeric|between:-90,90',
            'originLng' => 'required|numeric|between:-180,180',
            'destinationSpotId' => 'required|string',
        ]);

        return response()->json(
            $this->traffic->route((float) $data['originLat'], (float) $data['originLng'], $data['destinationSpotId'])
        );
    }

    public function config(): JsonResponse
    {
        $this->settings->ensureDefaults();

        return response()->json($this->settings->mobileConfig());
    }

    public function providers(): JsonResponse
    {
        $config = $this->settings->mobileConfig();

        return response()->json([
            'providers' => $config['providers'],
            'features' => $config['features'],
        ]);
    }

    public function health(): JsonResponse
    {
        $database = config('database.default');
        $ok = true;

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable $e) {
            $ok = false;
            $database = 'unavailable';
        }

        $providers = $this->settings->mobileConfig()['providers'];

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'database' => $database,
            'providers' => $providers,
        ], $ok ? 200 : 503);
    }
}
