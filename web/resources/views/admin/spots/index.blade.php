@extends('admin.layout')

@section('title', 'Parking spots')
@section('subtitle', 'OSM, City Parking, and user-submitted bays')

@section('content')
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; flex:1;">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search name, city, OSM id">
        <button type="submit" class="secondary">Search</button>
    </form>
    <a class="btn" href="{{ route('admin.spots.create') }}">Add spot</a>
    <form method="POST" action="{{ route('admin.spots.ingest') }}">
        @csrf
        <button type="submit" class="secondary">Ingest Zimbabwe OSM</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
        <tr><th>Name</th><th>Location</th><th>City</th><th>Status</th><th>Source</th><th></th></tr>
        </thead>
        <tbody>
        @foreach ($spots as $spot)
            @php $status = $spot->latestActiveReport?->status; @endphp
            <tr>
                <td>{{ $spot->name ?: 'Unnamed' }}</td>
                <td>{{ number_format($spot->lat, 5) }}, {{ number_format($spot->lng, 5) }}</td>
                <td>{{ $spot->city ?: '—' }}</td>
                <td>
                    @if ($status === 'FREE') <span class="badge free">FREE</span>
                    @elseif ($status === 'TAKEN') <span class="badge taken">TAKEN</span>
                    @else <span class="badge">unknown</span>
                    @endif
                </td>
                <td>{{ $spot->source }}</td>
                <td class="row-actions">
                    <a class="btn secondary" href="{{ route('admin.spots.edit', $spot) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.spots.destroy', $spot) }}" onsubmit="return confirm('Delete this spot?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:12px;">{{ $spots->links() }}</div>
</div>
@endsection
