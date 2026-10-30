<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · OpenLot</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,650&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/openlot.css') }}">
</head>
<body>
<div class="ol-backdrop" data-ol-backdrop></div>
<div class="ol-shell">
    <aside class="ol-side" id="ol-side" aria-label="Admin navigation">
        <div class="ol-brand">
            <div class="ol-brand-mark" aria-hidden="true">O</div>
            <div>
                <div class="ol-brand-text">OpenLot</div>
                <span class="ol-brand-sub">Admin</span>
            </div>
        </div>
        <nav class="ol-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="ol-nav-ico" aria-hidden="true">◇</span> Dashboard</a>
            <a href="{{ route('admin.spots.index') }}" class="{{ request()->routeIs('admin.spots.*') ? 'active' : '' }}"><span class="ol-nav-ico" aria-hidden="true">▣</span> Parking spots</a>
            <a href="{{ route('admin.fee-rules.index') }}" class="{{ request()->routeIs('admin.fee-rules.*') ? 'active' : '' }}"><span class="ol-nav-ico" aria-hidden="true">$</span> Fee rules</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="ol-nav-ico" aria-hidden="true">◎</span> Users</a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><span class="ol-nav-ico" aria-hidden="true">☰</span> Reports</a>
            <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><span class="ol-nav-ico" aria-hidden="true">⚙</span> API settings</a>
        </nav>
        <div class="ol-side-foot">openlot.companyop.com</div>
    </aside>

    <main class="ol-main">
        <div class="ol-topbar">
            <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                <button type="button" class="ol-menu-btn" data-ol-menu aria-controls="ol-side" aria-expanded="false" aria-label="Open menu">
                    <span></span>
                </button>
                <div>
                    <h1>@yield('title', 'Admin')</h1>
                    <div class="ol-subtitle">@yield('subtitle')</div>
                </div>
            </div>
            <div class="ol-top-actions">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="secondary">Log out</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="ol-flash flash" role="status">
                <span>{{ session('status') }}</span>
                <button type="button" class="ol-flash-close" data-ol-dismiss aria-label="Dismiss">×</button>
            </div>
        @endif
        @if ($errors->any())
            <div class="ol-errors errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
<script src="{{ asset('js/openlot.js') }}" defer></script>
</body>
</html>
