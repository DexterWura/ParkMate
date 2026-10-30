<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login · OpenLot</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,650&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/openlot.css') }}">
</head>
<body class="ol-auth">
<div class="ol-auth-card">
    <div class="ol-brand" style="margin-bottom:1rem;">
        <div class="ol-brand-mark" aria-hidden="true">O</div>
        <div>
            <div class="ol-brand-text" style="color:var(--navy);">OpenLot</div>
            <span class="ol-brand-sub" style="color:var(--navy-muted);">Admin console</span>
        </div>
    </div>
    <h1>Sign in</h1>
    <p>Manage parking data, occupancy reports, users, and API providers.</p>
    @if ($errors->any())
        <div class="ol-errors errors" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="ol-field field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="ol-field field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <label class="ol-check-row">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button type="submit" class="primary">Sign in</button>
    </form>
</div>
</body>
</html>
