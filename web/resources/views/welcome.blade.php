<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'OpenLot') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,650&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/openlot.css') }}">
</head>
<body class="ol-site">
    <header class="ol-site-nav">
        <div class="ol-brand">
            <div class="ol-brand-mark" aria-hidden="true">O</div>
            <div class="ol-brand-text">OpenLot</div>
        </div>
        <nav class="ol-site-links" aria-label="Primary">
            <a href="#how">How it works</a>
            <a href="#api">API</a>
            <a href="{{ url('/admin') }}">Admin</a>
        </nav>
    </header>

    <section class="ol-hero">
        <div class="ol-hero-inner">
            <span class="ol-hero-kicker">Parking for Zimbabwe cities</span>
            <h1>OpenLot<em>.</em></h1>
            <p class="ol-hero-lead">
                Find free and taken bays on OpenStreetMap, estimate City Parking fees,
                and see route traffic — managed from a simple admin console.
            </p>
            <div class="ol-hero-actions">
                <a class="ol-btn ol-btn-primary" href="{{ url('/admin') }}">Open admin</a>
                <a class="ol-btn ol-btn-secondary" href="#api">View API</a>
            </div>
        </div>
    </section>

    <section class="ol-section" id="how">
        <h2>How it works</h2>
        <p class="ol-muted">One job per layer: map data, crowd reports, and admin control.</p>
        <div class="ol-feature-grid">
            <article class="ol-feature">
                <span class="ol-feature-num">01</span>
                <strong>Map the lots</strong>
                <p>OSM Overpass ingest plus curated City Parking spots for Harare and beyond.</p>
            </article>
            <article class="ol-feature">
                <span class="ol-feature-num">02</span>
                <strong>Report occupancy</strong>
                <p>Drivers mark bays free or taken. Reports expire on a TTL you control.</p>
            </article>
            <article class="ol-feature">
                <span class="ol-feature-num">03</span>
                <strong>Serve the app</strong>
                <p>The Android client reads only your API — Mapbox, TomTom, and Nominatim stay server-side.</p>
            </article>
        </div>
    </section>

    <section class="ol-section" id="api">
        <h2>Mobile API</h2>
        <p class="ol-muted">Base path <code>/api/v1</code>. Health and config are public; spots and routes power the map.</p>
        <div class="ol-card" style="margin-top:1rem;">
            <div class="ol-table-wrap">
                <table>
                    <thead>
                    <tr><th>Method</th><th>Path</th><th>Purpose</th></tr>
                    </thead>
                    <tbody>
                    <tr><td>GET</td><td><code>/health</code></td><td>Database and provider status</td></tr>
                    <tr><td>GET</td><td><code>/config</code></td><td>Map defaults and feature flags</td></tr>
                    <tr><td>GET</td><td><code>/spots</code></td><td>Viewport list with filters</td></tr>
                    <tr><td>GET</td><td><code>/spots/nearby</code></td><td>Radius search</td></tr>
                    <tr><td>POST</td><td><code>/routes</code></td><td>Driving route with fallback providers</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <footer class="ol-footer">
        Map data © OpenStreetMap contributors · {{ config('app.name', 'OpenLot') }} · {{ date('Y') }}
    </footer>
    <script src="{{ asset('js/openlot.js') }}" defer></script>
</body>
</html>
