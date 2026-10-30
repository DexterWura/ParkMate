@extends('admin.layout')

@section('title', $spot->exists ? 'Edit spot' : 'Add spot')
@section('subtitle', $spot->name)

@section('content')
<div class="card" style="max-width:720px;">
    <form method="POST" action="{{ $spot->exists ? route('admin.spots.update', $spot) : route('admin.spots.store') }}">
        @csrf
        @if ($spot->exists) @method('PUT') @endif
        <div class="field"><label>Name</label><input name="name" value="{{ old('name', $spot->name) }}"></div>
        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div class="field"><label>Latitude</label><input name="lat" value="{{ old('lat', $spot->lat) }}" required></div>
            <div class="field"><label>Longitude</label><input name="lng" value="{{ old('lng', $spot->lng) }}" required></div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div class="field"><label>City</label><input name="city" value="{{ old('city', $spot->city) }}"></div>
            <div class="field"><label>Parking type</label><input name="parking_type" value="{{ old('parking_type', $spot->parking_type) }}"></div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div class="field"><label>Access</label><input name="access_type" value="{{ old('access_type', $spot->access_type) }}"></div>
            <div class="field"><label>Capacity</label><input name="capacity" value="{{ old('capacity', $spot->capacity) }}"></div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div class="field"><label>OSM fee</label><input name="osm_fee" value="{{ old('osm_fee', $spot->osm_fee) }}"></div>
            <div class="field"><label>OSM charge</label><input name="osm_charge" value="{{ old('osm_charge', $spot->osm_charge) }}"></div>
        </div>
        <div class="field"><label>Source</label><input name="source" value="{{ old('source', $spot->source ?: 'ADMIN') }}"></div>
        <div class="field"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $spot->is_active ?? true))> Active</label></div>
        <div class="row-actions">
            <button type="submit">Save</button>
            <a class="btn secondary" href="{{ route('admin.spots.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
