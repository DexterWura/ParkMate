@extends('admin.layout')

@section('title', $spot->exists ? 'Edit spot' : 'Add spot')
@section('subtitle', $spot->name)

@section('content')
<div class="ol-card card" style="max-width:720px;">
    <form method="POST" action="{{ $spot->exists ? route('admin.spots.update', $spot) : route('admin.spots.store') }}">
        @csrf
        @if ($spot->exists) @method('PUT') @endif
        <div class="ol-field field"><label for="name">Name</label><input id="name" name="name" value="{{ old('name', $spot->name) }}"></div>
        <div class="ol-grid-2">
            <div class="ol-field field"><label for="lat">Latitude</label><input id="lat" name="lat" value="{{ old('lat', $spot->lat) }}" required></div>
            <div class="ol-field field"><label for="lng">Longitude</label><input id="lng" name="lng" value="{{ old('lng', $spot->lng) }}" required></div>
        </div>
        <div class="ol-grid-2">
            <div class="ol-field field"><label for="city">City</label><input id="city" name="city" value="{{ old('city', $spot->city) }}"></div>
            <div class="ol-field field"><label for="parking_type">Parking type</label><input id="parking_type" name="parking_type" value="{{ old('parking_type', $spot->parking_type) }}"></div>
        </div>
        <div class="ol-grid-2">
            <div class="ol-field field"><label for="access_type">Access</label><input id="access_type" name="access_type" value="{{ old('access_type', $spot->access_type) }}"></div>
            <div class="ol-field field"><label for="capacity">Capacity</label><input id="capacity" name="capacity" value="{{ old('capacity', $spot->capacity) }}"></div>
        </div>
        <div class="ol-grid-2">
            <div class="ol-field field"><label for="osm_fee">OSM fee</label><input id="osm_fee" name="osm_fee" value="{{ old('osm_fee', $spot->osm_fee) }}"></div>
            <div class="ol-field field"><label for="osm_charge">OSM charge</label><input id="osm_charge" name="osm_charge" value="{{ old('osm_charge', $spot->osm_charge) }}"></div>
        </div>
        <div class="ol-field field"><label for="source">Source</label><input id="source" name="source" value="{{ old('source', $spot->source ?: 'ADMIN') }}"></div>
        <div class="ol-field field">
            <label class="ol-check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $spot->is_active ?? true))> Active</label>
        </div>
        <div class="ol-row-actions row-actions">
            <button type="submit" class="primary">Save</button>
            <a class="ol-btn btn secondary" href="{{ route('admin.spots.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
