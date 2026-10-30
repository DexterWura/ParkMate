@extends('admin.layout')

@section('title', 'Occupancy reports')
@section('subtitle', 'Crowd-sourced free / taken signals')

@section('content')
<div class="ol-card card">
    <div class="ol-table-wrap">
        <table>
            <thead>
            <tr><th>Spot</th><th>Status</th><th>Device</th><th>Expires</th><th>Created</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($reports as $report)
                <tr>
                    <td>{{ $report->spot?->name ?: $report->parking_spot_id }}</td>
                    <td><span class="ol-badge badge {{ strtolower($report->status) }}">{{ $report->status }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($report->device_id, 12) }}</td>
                    <td>{{ $report->expires_at }}</td>
                    <td>{{ $report->created_at }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" data-ol-confirm="Remove this report?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="secondary">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="ol-empty"><strong>No reports yet</strong>Occupancy signals from the Android app will show up here.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px;">{{ $reports->links() }}</div>
</div>
@endsection
