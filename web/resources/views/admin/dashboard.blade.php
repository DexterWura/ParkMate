@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Live overview of OpenLot')

@section('content')
<div class="ol-grid grid">
    <div class="ol-card card ol-stat stat" data-accent="orange">
        <div class="ol-muted muted">Active spots</div>
        <strong>{{ $spotCount }}</strong>
    </div>
    <div class="ol-card card ol-stat stat">
        <div class="ol-muted muted">Users</div>
        <strong>{{ $userCount }}</strong>
    </div>
    <div class="ol-card card ol-stat stat">
        <div class="ol-muted muted">Fresh reports</div>
        <strong>{{ $reportCount }}</strong>
    </div>
</div>

<div class="ol-grid-2">
    <div class="ol-card card">
        <h3>Recent spots</h3>
        <div class="ol-table-wrap">
            <table>
                <thead><tr><th>Name</th><th>City</th><th>Source</th></tr></thead>
                <tbody>
                @forelse ($recentSpots as $spot)
                    <tr>
                        <td>{{ $spot->name ?: 'Unnamed' }}</td>
                        <td>{{ $spot->city ?: '—' }}</td>
                        <td>{{ $spot->source }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="ol-muted muted">No spots yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="ol-card card">
        <h3>Recent reports</h3>
        <div class="ol-table-wrap">
            <table>
                <thead><tr><th>Spot</th><th>Status</th><th>When</th></tr></thead>
                <tbody>
                @forelse ($recentReports as $report)
                    <tr>
                        <td>{{ $report->spot?->name ?: $report->parking_spot_id }}</td>
                        <td><span class="ol-badge badge {{ strtolower($report->status) }}">{{ $report->status }}</span></td>
                        <td>{{ $report->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="ol-muted muted">No reports yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
