<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Install {{ config('app.name', 'Shipit') }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #080b12;
            --panel: #111621;
            --panel-soft: #171d2a;
            --border: #293142;
            --text: #f3f6fb;
            --muted: #9ba7ba;
            --brand: #7c8cff;
            --brand-hover: #94a0ff;
            --success: #52d89d;
            --danger: #ff7a90;
            --warning: #f3c96b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 0%, rgba(124, 140, 255, .16), transparent 28rem),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        button, input, select { font: inherit; }
        .shell { width: min(960px, calc(100% - 2rem)); margin: 0 auto; padding: 3rem 0 5rem; }
        .brand { display: flex; align-items: center; gap: .75rem; margin-bottom: 2rem; font-weight: 750; }
        .brand-mark {
            display: grid;
            width: 2.35rem;
            height: 2.35rem;
            place-items: center;
            border-radius: .75rem;
            background: var(--brand);
            color: #070a11;
        }

        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: .7rem; margin: 0 0 1.25rem; padding: 0; list-style: none; }
        .steps li { padding: .8rem 1rem; border: 1px solid var(--border); border-radius: .8rem; color: var(--muted); }
        .steps strong { display: block; color: var(--text); font-size: .86rem; }
        .steps .active { border-color: var(--brand); background: rgba(124, 140, 255, .08); }

        .panel { padding: clamp(1.25rem, 3vw, 2rem); border: 1px solid var(--border); border-radius: 1rem; background: rgba(17, 22, 33, .96); box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .22); }
        .panel + .panel { margin-top: 1rem; }
        h1, h2 { margin: 0; line-height: 1.15; }
        h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); letter-spacing: -.035em; }
        h2 { font-size: 1.15rem; }
        .lead { max-width: 42rem; margin: .75rem 0 0; color: var(--muted); }
        .section-heading { margin-bottom: 1rem; }
        .section-heading p { margin: .3rem 0 0; color: var(--muted); font-size: .92rem; }

        .alert { margin: 1rem 0 0; padding: .9rem 1rem; border: 1px solid; border-radius: .7rem; }
        .alert-danger { border-color: rgba(255, 122, 144, .5); background: rgba(255, 122, 144, .08); color: #ffd7de; }
        .alert-warning { border-color: rgba(243, 201, 107, .45); background: rgba(243, 201, 107, .08); color: #ffedc4; }

        .checks { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; margin-top: 1.2rem; }
        .check { display: flex; align-items: flex-start; gap: .7rem; padding: .85rem; border: 1px solid var(--border); border-radius: .7rem; background: var(--panel-soft); }
        .check-icon { flex: 0 0 auto; display: grid; width: 1.35rem; height: 1.35rem; place-items: center; border-radius: 50%; font-size: .72rem; font-weight: 800; }
        .passed .check-icon { background: rgba(82, 216, 157, .16); color: var(--success); }
        .failed .check-icon { background: rgba(255, 122, 144, .14); color: var(--danger); }
        .optional.failed .check-icon { background: rgba(243, 201, 107, .14); color: var(--warning); }
        .check strong, .check small { display: block; }
        .check strong { font-size: .88rem; }
        .check small { margin-top: .15rem; color: var(--muted); }

        fieldset { margin: 0; padding: 0; border: 0; }
        fieldset + fieldset { margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); }
        legend { width: 100%; margin-bottom: 1rem; padding: 0; font-size: 1.08rem; font-weight: 700; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .field-full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: .4rem; font-size: .88rem; font-weight: 650; }
        input, select {
            width: 100%;
            min-height: 2.85rem;
            padding: .65rem .75rem;
            border: 1px solid var(--border);
            border-radius: .62rem;
            outline: none;
            background: #0d111a;
            color: var(--text);
        }
        input:focus, select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(124, 140, 255, .14); }
        .hint, .error { display: block; margin-top: .35rem; font-size: .78rem; }
        .hint { color: var(--muted); }
        .error { color: var(--danger); }
        .actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--border); }
        .actions p { margin: 0; color: var(--muted); font-size: .82rem; }
        .button {
            min-width: 10rem;
            padding: .72rem 1.1rem;
            border: 0;
            border-radius: .65rem;
            background: var(--brand);
            color: #080b12;
            font-weight: 750;
            cursor: pointer;
        }
        .button:hover { background: var(--brand-hover); }
        .button:disabled { cursor: not-allowed; opacity: .45; }
        [hidden] { display: none !important; }

        @media (max-width: 680px) {
            .shell { padding-top: 1.5rem; }
            .steps, .checks, .grid { grid-template-columns: 1fr; }
            .steps li { display: none; }
            .steps .active { display: block; }
            .field-full { grid-column: auto; }
            .actions { align-items: stretch; flex-direction: column; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
    @php($connection = old('db_connection', $selectedConnection ?? 'sqlite'))
    <main class="shell">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span>Shipit installer</span>
        </div>

        <ol class="steps" aria-label="Installation progress">
            <li><strong>1. Server check</strong>Confirm this host is ready</li>
            <li class="active" aria-current="step"><strong>2. Configure</strong>Connect and create the site</li>
            <li><strong>3. Complete</strong>Lock the installer</li>
        </ol>

        <section class="panel">
            <h1>Set up your site</h1>
            <p class="lead">The installer will configure the environment, create the database schema with Laravel migrations, and add your first verified account.</p>

            @if (session('install_error'))
                <div class="alert alert-danger" role="alert">{{ session('install_error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">Some details need your attention. Review the highlighted fields below.</div>
            @endif
        </section>

        <section class="panel" aria-labelledby="preflight-title">
            <div class="section-heading">
                <h2 id="preflight-title">Server preflight</h2>
                <p>Required checks must pass before installation can begin.</p>
            </div>

            <div class="checks">
                @foreach ($preflight['checks'] as $check)
                    <div class="check {{ $check['passed'] ? 'passed' : 'failed' }} {{ $check['required'] ? 'required' : 'optional' }}">
                        <span class="check-icon" aria-hidden="true">{{ $check['passed'] ? '✓' : '!' }}</span>
                        <span>
                            <strong>{{ $check['label'] }}</strong>
                            <small>{{ $check['message'] }}{{ ! $check['required'] ? ' (optional for this selection)' : '' }}</small>
                        </span>
                    </div>
                @endforeach
            </div>

            @if (! $preflight['ready'])
                <div class="alert alert-warning" role="alert">Fix the failed required checks and reload this page before continuing.</div>
            @endif
        </section>

        <form class="panel" method="POST" action="{{ route('install.store') }}" id="install-form">
            @csrf

            <fieldset>
                <legend>Site details</legend>
                <div class="grid">
                    <div>
                        <label for="app_name">Site name</label>
                        <input id="app_name" name="app_name" value="{{ old('app_name', config('app.name', 'Shipit')) }}" maxlength="255" required autocomplete="organization">
                        @error('app_name')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label for="app_url">Site URL</label>
                        <input id="app_url" name="app_url" type="url" value="{{ old('app_url', url('/')) }}" maxlength="2048" required placeholder="https://example.com" autocomplete="url">
                        @error('app_url')<span class="error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Database</legend>
                <div class="grid">
                    <div class="field-full">
                        <label for="db_connection">Database type</label>
                        <select id="db_connection" name="db_connection" required>
                            <option value="sqlite" @selected($connection === 'sqlite')>SQLite</option>
                            <option value="mysql" @selected(in_array($connection, ['mysql', 'mariadb'], true))>MySQL / MariaDB</option>
                        </select>
                        <span class="hint" id="database-hint">SQLite uses the fixed database/database.sqlite file on this server.</span>
                        @error('db_connection')<span class="error">{{ $message }}</span>@enderror
                    </div>

                    <div class="mysql-field">
                        <label for="db_host">Host</label>
                        <input id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" maxlength="255" autocomplete="off">
                        @error('db_host')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="mysql-field">
                        <label for="db_port">Port</label>
                        <input id="db_port" name="db_port" type="number" value="{{ old('db_port', '3306') }}" min="1" max="65535" inputmode="numeric">
                        @error('db_port')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="mysql-field">
                        <label for="db_database">Database name</label>
                        <input id="db_database" name="db_database" value="{{ old('db_database', 'shipit') }}" maxlength="64" pattern="[A-Za-z_][A-Za-z0-9_]*" autocomplete="off">
                        @error('db_database')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="mysql-field">
                        <label for="db_username">Username</label>
                        <input id="db_username" name="db_username" value="{{ old('db_username') }}" maxlength="255" autocomplete="username">
                        @error('db_username')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="mysql-field field-full">
                        <label for="db_password">Password</label>
                        <input id="db_password" name="db_password" type="password" maxlength="4096" autocomplete="new-password">
                        <span class="hint">This value is written to .env and is never redisplayed.</span>
                        @error('db_password')<span class="error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>First account</legend>
                <div class="grid">
                    <div class="field-full">
                        <label for="owner_name">Name</label>
                        <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" maxlength="255" required autocomplete="name">
                        @error('owner_name')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field-full">
                        <label for="owner_email">Email address</label>
                        <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" maxlength="255" required autocomplete="email">
                        @error('owner_email')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label for="owner_password">Password</label>
                        <input id="owner_password" name="owner_password" type="password" required autocomplete="new-password">
                        @error('owner_password')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label for="owner_password_confirmation">Confirm password</label>
                        <input id="owner_password_confirmation" name="owner_password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>
            </fieldset>

            <div class="actions">
                <p>Installation may take a minute. Keep this page open until it completes.</p>
                <button class="button" type="submit" @disabled(! $preflight['ready'])>
                    <span class="button-label">Install site</span>
                </button>
            </div>
        </form>
    </main>

    <script>
        (() => {
            const form = document.getElementById('install-form');
            const connection = document.getElementById('db_connection');
            const mysqlFields = [...document.querySelectorAll('.mysql-field')];
            const hint = document.getElementById('database-hint');
            const button = form.querySelector('button[type="submit"]');
            const label = button.querySelector('.button-label');
            const checkedConnection = @json($selectedConnection);

            const updateDatabaseFields = () => {
                const mysql = connection.value === 'mysql';

                mysqlFields.forEach((wrapper) => {
                    wrapper.hidden = !mysql;
                    wrapper.querySelectorAll('input').forEach((input) => {
                        input.disabled = !mysql;
                        input.required = mysql && input.id !== 'db_password';
                    });
                });

                hint.textContent = mysql
                    ? 'The database will be created if the supplied account has CREATE DATABASE permission.'
                    : 'SQLite uses the fixed database/database.sqlite file on this server.';
            };

            connection.addEventListener('change', () => {
                updateDatabaseFields();

                if (connection.value !== checkedConnection) {
                    const installerUrl = new URL(@json(route('install.index')), window.location.href);
                    installerUrl.searchParams.set('db_connection', connection.value);
                    window.location.assign(installerUrl);
                }
            });
            updateDatabaseFields();

            form.addEventListener('submit', () => {
                button.disabled = true;
                label.textContent = 'Installing…';
            });
        })();
    </script>
</body>
</html>
