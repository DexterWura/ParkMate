<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login · ParkMate</title>
    <style>
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(31,157,85,0.18), transparent 40%),
                linear-gradient(160deg, #f7f7f5, #e8e7e1);
            color: #111;
        }
        .card {
            width: min(420px, 92vw); background: #fff; border-radius: 20px;
            padding: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.08);
        }
        h1 { margin: 0 0 8px; letter-spacing: -0.03em; }
        p { color: #5c5c5c; margin-top: 0; }
        label { display:block; margin: 14px 0 6px; color: #5c5c5c; font-size: 0.9rem; }
        input { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e4e4e0; font: inherit; }
        button {
            width: 100%; margin-top: 18px; border: 0; border-radius: 999px;
            background: #111; color: #fff; padding: 12px; font-weight: 700; cursor: pointer;
        }
        .errors { background: #fdecec; color: #7f1d1d; padding: 10px 12px; border-radius: 12px; }
    </style>
</head>
<body>
<div class="card">
    <h1>ParkMate</h1>
    <p>Admin console for parking data, users, and API keys.</p>
    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <label><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit">Sign in</button>
    </form>
</div>
</body>
</html>
