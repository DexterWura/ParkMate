<?php

namespace App\Domains\Install\Providers;

use App\Domains\Install\Services\ApplicationKeyService;
use App\Domains\Install\Services\InstallationState;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InstallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApplicationKeyService::class);
        $this->app->singleton(InstallationState::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'installer.only'])
            ->group(base_path('routes/domains/install.php'));
    }
}
