@extends('admin.layout')

@section('title', 'API settings')
@section('subtitle', 'Map providers, feature flags, and mobile defaults')

@section('content')
<div class="ol-card card" style="max-width:860px;">
    <p class="ol-muted muted" style="margin-top:0;">
        These values power <code>/api/v1/config</code> for the Android app. Secrets stay on the server —
        the mobile client only receives feature flags and whether a provider is configured.
    </p>
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @foreach ($settings as $group => $rows)
            <h3 style="margin-top:1.25rem;padding-top:0.85rem;border-top:1px solid var(--line);">{{ ucfirst($group) }}</h3>
            @foreach ($rows as $setting)
                <div class="ol-field field">
                    <label for="setting-{{ $setting->key }}">{{ $setting->label ?: $setting->key }}</label>
                    @if (!empty($help[$setting->key]))
                        <span class="hint">{{ $help[$setting->key] }}</span>
                    @endif
                    <input
                        id="setting-{{ $setting->key }}"
                        name="settings[{{ $setting->key }}]"
                        type="{{ $setting->is_secret ? 'password' : 'text' }}"
                        value="{{ $setting->is_secret ? '' : $setting->value }}"
                        placeholder="{{ $setting->is_secret ? (filled($setting->value) ? '•••• saved — leave blank to keep' : 'Not set') : '' }}"
                        autocomplete="off"
                    >
                </div>
            @endforeach
        @endforeach
        <button type="submit" class="primary">Save settings</button>
    </form>
</div>
@endsection
