@extends('admin.layout')

@section('title', 'API settings')
@section('subtitle', 'Mapbox, TomTom, Overpass, and app defaults')

@section('content')
<div class="card" style="max-width:820px;">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @foreach ($settings as $group => $rows)
            <h3 style="margin-top:8px;">{{ ucfirst($group) }}</h3>
            @foreach ($rows as $setting)
                <div class="field">
                    <label for="setting-{{ $setting->key }}">{{ $setting->label ?: $setting->key }}</label>
                    <input
                        id="setting-{{ $setting->key }}"
                        name="settings[{{ $setting->key }}]"
                        type="{{ $setting->is_secret ? 'password' : 'text' }}"
                        value="{{ $setting->is_secret ? '' : $setting->value }}"
                        placeholder="{{ $setting->is_secret ? (filled($setting->value) ? '•••• saved — leave blank to keep' : 'Not set') : '' }}"
                    >
                </div>
            @endforeach
        @endforeach
        <button type="submit">Save settings</button>
    </form>
</div>
@endsection
