<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Install {{ config('app.name', 'OpenLot') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,650&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/openlot.css') }}">
</head>
<body class="ol-install">
    @php($connection = old('db_connection', $selectedConnection ?? 'sqlite'))
    <main class="ol-install-shell shell">
        <div class="ol-brand brand" style="margin-bottom:1.5rem;">
            <span class="ol-brand-mark brand-mark" aria-hidden="true">O</span>
            <div>
                <div class="ol-brand-text" style="color:var(--navy);">OpenLot</div>
                <span class="ol-brand-sub" style="color:var(--navy-muted);">Installer</span>
            </div>
        </div>

        <ol class="ol-steps steps" aria-label="Installation progress">
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
                        <input id="app_name" name="app_name" value="{{ old('app_name', config('app.name', 'OpenLot')) }}" maxlength="255" required autocomplete="organization">
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
                        <input id="db_database" name="db_database" value="{{ old('db_database', 'openlot') }}" maxlength="64" pattern="[A-Za-z_][A-Za-z0-9_]*" autocomplete="off">
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
