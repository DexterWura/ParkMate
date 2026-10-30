<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · ParkMate</title>
    <style>
        :root {
            --ink: #111111;
            --muted: #5c5c5c;
            --bg: #f4f4f1;
            --card: #ffffff;
            --line: #e4e4e0;
            --accent: #111111;
            --free: #1f9d55;
            --taken: #d64545;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(160deg, #f7f7f5 0%, #ebeae4 100%);
            color: var(--ink);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .shell { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        .side {
            background: #141414;
            color: #f5f5f5;
            padding: 28px 20px;
        }
        .brand { font-size: 1.4rem; font-weight: 700; letter-spacing: -0.03em; margin-bottom: 28px; }
        .nav a {
            display: block;
            padding: 10px 12px;
            border-radius: 10px;
            color: #cfcfcf;
            margin-bottom: 4px;
        }
        .nav a:hover, .nav a.active { background: #2a2a2a; color: #fff; }
        .main { padding: 28px; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
        h1 { margin: 0; font-size: 1.7rem; letter-spacing: -0.03em; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        }
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px; }
        .stat strong { display: block; font-size: 1.8rem; margin-top: 6px; }
        .muted { color: var(--muted); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { font-size: 0.85rem; color: var(--muted); font-weight: 600; }
        .btn, button, .button {
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--accent); color: #fff; border: 0; border-radius: 999px;
            padding: 10px 16px; cursor: pointer; font-weight: 600;
        }
        .btn.secondary, button.secondary { background: #ecece8; color: var(--ink); }
        .btn.danger { background: var(--taken); }
        input, select, textarea {
            width: 100%; padding: 10px 12px; border-radius: 10px;
            border: 1px solid var(--line); background: #fff; font: inherit;
        }
        label { display: block; font-size: 0.9rem; margin-bottom: 6px; color: var(--muted); }
        .field { margin-bottom: 14px; }
        .flash { background: #e8f6ee; color: #14532d; padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; }
        .errors { background: #fdecec; color: #7f1d1d; padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; background: #eee; }
        .badge.free { background: #dcfce7; color: #166534; }
        .badge.taken { background: #fee2e2; color: #991b1b; }
        .row-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .side { padding-bottom: 10px; }
            .nav { display: flex; flex-wrap: wrap; gap: 6px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="side">
        <div class="brand">ParkMate</div>
        <nav class="nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.spots.index') }}" class="{{ request()->routeIs('admin.spots.*') ? 'active' : '' }}">Parking spots</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
            <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">API settings</a>
        </nav>
    </aside>
    <main class="main">
        <div class="top">
            <div>
                <h1>@yield('title', 'Admin')</h1>
                <div class="muted">@yield('subtitle')</div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="secondary">Log out</button>
            </form>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="errors">
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
</body>
</html>
