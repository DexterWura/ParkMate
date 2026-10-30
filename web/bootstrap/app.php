<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend([
            \App\Domains\Install\Http\Middleware\EnsureApplicationKey::class,
            \App\Domains\Install\Http\Middleware\EnsureApplicationIsInstalled::class,
        ]);

        $middleware->api(prepend: [
            \App\Core\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->append([
            \App\Core\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'installer.only' => \App\Domains\Install\Http\Middleware\EnsureApplicationIsNotInstalled::class,
            'verified' => \App\Core\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Core\Http\Middleware\EnsureAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Use env() here — config() is not available during early artisan bootstraps (e.g. package:discover).
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current_password',
            'db_password',
            'owner_password',
            'owner_password_confirmation',
            'password',
            'password_confirmation',
        ]);
    })
    ->create();
