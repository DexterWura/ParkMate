@extends('admin.layout')

@section('title', 'Occupancy reports')
@section('subtitle', 'Crowd-sourced free / taken signals')

@section('content')
<div class="card">
    <table>
        <thead>
        <tr><th>Spot</th><th>Status</th><th>Device</th><th>Expires</th><th>Created</th></tr>
        </thead>
        <tbody>
        @foreach ($reports as $report)
            <tr>
                <td>{{ $report->spot?->name ?: $report->parking_spot_id }}</td>
                <td><span class="badge {{ strtolower($report->status) }}">{{ $report->status }}</span></td>
                <td>{{ \Illuminate\Support\Str::limit($report->device_id, 12) }}</td>
                <td>{{ $report->expires_at }}</td>
                <td>{{ $report->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:12px;">{{ $reports->links() }}</div>
</div>
@endsection
