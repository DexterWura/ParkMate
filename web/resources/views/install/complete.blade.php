<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>OpenLot installed</title>
    @php($assetBase = rtrim(str_replace('\\', '/', request()->getBasePath()), '/'))
    <link rel="icon" href="{{ $assetBase }}/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,650&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ $assetBase }}/css/openlot.css">
</head>
<body class="ol-auth">
    <main class="ol-auth-card" style="text-align:left; width:min(560px,100%);">
        <div class="ol-brand" style="margin-bottom:1rem;">
            <span class="ol-brand-mark" aria-hidden="true">✓</span>
            <div>
                <div class="ol-brand-text" style="color:var(--navy);">OpenLot</div>
                <span class="ol-brand-sub" style="color:var(--navy-muted);">Installed</span>
            </div>
        </div>
        <h1 style="margin:0 0 0.5rem;">Ready to go</h1>
        <p style="margin:0 0 1rem;color:var(--muted);">
            The database is migrated, sample parking data is seeded, API settings defaults are in place,
            and the installer is locked. Sign in with the admin account you just created.
        </p>
        <ol style="margin:0 0 1.25rem;padding-left:1.2rem;color:var(--navy-muted);font-size:0.95rem;">
            <li style="margin-bottom:0.4rem;">Open <strong>Admin</strong> and sign in</li>
            <li style="margin-bottom:0.4rem;">Add Mapbox / TomTom keys under <strong>API settings</strong></li>
            <li>Point the Android release build at <code>/api/v1</code></li>
        </ol>
        <div style="display:flex;flex-wrap:wrap;gap:0.65rem;">
            <a class="ol-btn ol-btn-primary" href="{{ $assetBase }}/admin/login">Open admin login</a>
            <a class="ol-btn ol-btn-secondary" href="{{ $assetBase }}/">View site home</a>
        </div>
    </main>
</body>
</html>
