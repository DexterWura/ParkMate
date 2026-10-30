<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Installation complete</title>
    <style>
        :root { color-scheme: dark; --bg: #080b12; --panel: #111621; --border: #293142; --text: #f3f6fb; --muted: #9ba7ba; --brand: #7c8cff; --success: #52d89d; }
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            place-items: center;
            background: radial-gradient(circle at 50% 0%, rgba(124, 140, 255, .18), transparent 30rem), var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        main { width: min(580px, calc(100% - 2rem)); padding: clamp(1.5rem, 5vw, 2.5rem); border: 1px solid var(--border); border-radius: 1rem; background: var(--panel); text-align: center; box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .25); }
        .icon { display: grid; width: 4rem; height: 4rem; margin: 0 auto 1.25rem; place-items: center; border-radius: 50%; background: rgba(82, 216, 157, .12); color: var(--success); font-size: 1.7rem; font-weight: 800; }
        h1 { margin: 0; font-size: clamp(1.8rem, 6vw, 2.5rem); letter-spacing: -.035em; }
        p { margin: .8rem auto 0; color: var(--muted); line-height: 1.6; }
        a { display: inline-block; margin-top: 1.6rem; padding: .75rem 1.15rem; border-radius: .65rem; background: var(--brand); color: #080b12; font-weight: 750; text-decoration: none; }
        a:focus-visible { outline: 3px solid rgba(124, 140, 255, .4); outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <div class="icon" aria-hidden="true">✓</div>
        <h1>Installation complete</h1>
        <p>The database is ready, your first account has been created, and the installer is now locked. Future requests will open the site normally.</p>
        <a href="{{ url('/') }}">Open {{ config('app.name', 'Shipit') }}</a>
    </main>
</body>
</html>
