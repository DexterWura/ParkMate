@extends('admin.layout')

@section('title', 'Parking spots')
@section('subtitle', 'OSM, City Parking, and user-submitted bays')

@section('content')
<div class="ol-toolbar toolbar">
    <form method="GET">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search name, city, OSM id" aria-label="Search spots">
        <button type="submit" class="secondary">Search</button>
    </form>
    <a class="ol-btn btn primary" href="{{ route('admin.spots.create') }}">Add spot</a>
    <form method="POST" action="{{ route('admin.spots.ingest') }}" data-ol-confirm="Queue OSM ingest for Zimbabwe cities?">
        @csrf
        <button type="submit" class="secondary">Ingest Zimbabwe OSM</button>
    </form>
</div>

<div class="ol-card card">
    <div class="ol-table-wrap">
        <table>
            <thead>
            <tr><th>Name</th><th>Location</th><th>City</th><th>Status</th><th>Source</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($spots as $spot)
                @php $status = $spot->latestActiveReport?->status; @endphp
                <tr>
                    <td>{{ $spot->name ?: 'Unnamed' }}</td>
                    <td>{{ number_format($spot->lat, 5) }}, {{ number_format($spot->lng, 5) }}</td>
                    <td>{{ $spot->city ?: '—' }}</td>
                    <td>
                        @if ($status === 'FREE') <span class="ol-badge badge free">FREE</span>
                        @elseif ($status === 'TAKEN') <span class="ol-badge badge taken">TAKEN</span>
                        @else <span class="ol-badge badge">unknown</span>
                        @endif
                    </td>
                    <td>{{ $spot->source }}</td>
                    <td class="ol-row-actions row-actions">
                        <a class="ol-btn btn secondary" href="{{ route('admin.spots.edit', $spot) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.spots.destroy', $spot) }}" data-ol-confirm="Delete this spot?">
                            @csrf @method('DELETE')
                            <button type="submit" class="danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="ol-empty"><strong>No parking spots yet</strong>Add a bay or run Zimbabwe OSM ingest.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px;">{{ $spots->links() }}</div>
</div>
@endsection
