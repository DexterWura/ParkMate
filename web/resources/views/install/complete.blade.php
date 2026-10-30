<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Installation complete</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,650&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/openlot.css') }}">
</head>
<body class="ol-auth">
    <main class="ol-auth-card" style="text-align:center; width:min(520px,100%);">
        <div class="ol-brand-mark" style="margin:0 auto 1rem; width:3rem; height:3rem; font-size:1.25rem;" aria-hidden="true">✓</div>
        <h1>Installation complete</h1>
        <p>The database is ready, your first account has been created, and the installer is locked.</p>
        <a class="ol-btn ol-btn-primary" href="{{ url('/') }}" style="margin-top:1.25rem;">Open {{ config('app.name', 'OpenLot') }}</a>
    </main>
</body>
</html>
