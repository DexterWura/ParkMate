<?php

namespace App\Domains\{{Domain}}\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class {{Domain}}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories and services here.
    }

    public function boot(): void
    {
        // Register policies, events, and routes.
        // Route::prefix('api/v1/{{domain}}')
        //     ->middleware(['api', 'auth:sanctum'])
        //     ->group(base_path('routes/domains/{{domain}}.php'));
    }
}
