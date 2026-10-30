@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Live overview of ParkMate')

@section('content')
<div class="grid">
    <div class="card stat"><div class="muted">Active spots</div><strong>{{ $spotCount }}</strong></div>
    <div class="card stat"><div class="muted">Users</div><strong>{{ $userCount }}</strong></div>
    <div class="card stat"><div class="muted">Fresh reports</div><strong>{{ $reportCount }}</strong></div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <h3>Recent spots</h3>
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
                <tr><td colspan="3" class="muted">No spots yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Recent reports</h3>
        <table>
            <thead><tr><th>Spot</th><th>Status</th><th>When</th></tr></thead>
            <tbody>
            @forelse ($recentReports as $report)
                <tr>
                    <td>{{ $report->spot?->name ?: $report->parking_spot_id }}</td>
                    <td><span class="badge {{ strtolower($report->status) }}">{{ $report->status }}</span></td>
                    <td>{{ $report->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No reports yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
