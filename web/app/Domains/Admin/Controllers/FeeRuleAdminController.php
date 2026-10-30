<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Parking\Models\FeeRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeeRuleAdminController extends Controller
{
    public function index(): View
    {
        $rules = FeeRule::query()
            ->with('spot')
            ->orderBy('city')
            ->orderBy('kind')
            ->get();

        return view('admin.fee-rules.index', compact('rules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        FeeRule::query()->create(array_merge($data, [
            'id' => (string) Str::uuid(),
        ]));

        return back()->with('status', 'Fee rule created.');
    }

    public function update(Request $request, FeeRule $feeRule): RedirectResponse
    {
        $feeRule->fill($this->validated($request))->save();

        return back()->with('status', 'Fee rule updated.');
    }

    public function destroy(FeeRule $feeRule): RedirectResponse
    {
        $feeRule->delete();

        return back()->with('status', 'Fee rule deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'city' => 'required|string|max:120',
            'kind' => 'required|in:ON_STREET,PARKADE,LOT',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'unit' => 'required|in:HOUR',
            'source_note' => 'nullable|string|max:500',
            'effective_from' => 'nullable|date',
            'parking_spot_id' => 'nullable|string|exists:parking_spots,id',
        ]);

        $data['currency'] = strtoupper($data['currency']);
        $data['parking_spot_id'] = $data['parking_spot_id'] ?: null;

        return $data;
    }
}
