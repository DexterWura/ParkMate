<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Parking\Models\ParkingSpot;
use App\Domains\Parking\Services\ParkingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SpotAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $spots = ParkingSpot::query()
            ->with('latestActiveReport')
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('osm_id', 'like', "%{$q}%");
            }))
            ->orderByDesc('updated_at')
            ->simplePaginate(25)
            ->withQueryString();

        return view('admin.spots.index', compact('spots', 'q'));
    }

    public function create(): View
    {
        return view('admin.spots.form', ['spot' => new ParkingSpot]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        ParkingSpot::query()->create(array_merge($data, [
            'id' => (string) Str::uuid(),
            'source' => $data['source'] ?? 'ADMIN',
            'is_active' => $request->boolean('is_active', true),
        ]));

        return redirect()->route('admin.spots.index')->with('status', 'Parking spot created.');
    }

    public function edit(ParkingSpot $spot): View
    {
        return view('admin.spots.form', compact('spot'));
    }

    public function update(Request $request, ParkingSpot $spot): RedirectResponse
    {
        $data = $this->validated($request);
        $spot->fill($data);
        $spot->is_active = $request->boolean('is_active');
        $spot->save();

        return redirect()->route('admin.spots.index')->with('status', 'Parking spot updated.');
    }

    public function destroy(ParkingSpot $spot): RedirectResponse
    {
        $spot->delete();

        return redirect()->route('admin.spots.index')->with('status', 'Parking spot deleted.');
    }

    public function ingest(ParkingCatalog $catalog): RedirectResponse
    {
        $catalog->ensureZimbabwePrefetch(async: true);

        return back()->with('status', 'Zimbabwe OSM ingest queued for Harare, Bulawayo, Mutare, and Gweru. Spots will appear as each city finishes.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'nullable|string|max:255',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'parking_type' => 'nullable|string|max:64',
            'access_type' => 'nullable|string|max:64',
            'capacity' => 'nullable|integer|min:1',
            'city' => 'nullable|string|max:120',
            'source' => 'nullable|string|max:64',
            'osm_fee' => 'nullable|string|max:64',
            'osm_charge' => 'nullable|string|max:120',
        ]);
    }
}
