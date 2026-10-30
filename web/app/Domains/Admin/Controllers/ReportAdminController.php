<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Parking\Models\OccupancyReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ReportAdminController extends Controller
{
    public function index(): View
    {
        $reports = OccupancyReport::query()
            ->with(['spot', 'user'])
            ->latest()
            ->simplePaginate(30);

        return view('admin.reports.index', compact('reports'));
    }

    public function destroy(OccupancyReport $report): RedirectResponse
    {
        $report->delete();

        return back()->with('status', 'Report removed.');
    }
}
