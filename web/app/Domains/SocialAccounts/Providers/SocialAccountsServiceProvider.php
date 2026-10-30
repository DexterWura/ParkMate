<?php

namespace App\Domains\SocialAccounts\Providers;

use App\Domains\SocialAccounts\Interfaces\SocialAccountRepositoryInterface;
use App\Domains\SocialAccounts\Repositories\SocialAccountRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SocialAccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SocialAccountRepositoryInterface::class,
            SocialAccountRepository::class
        );
    }

    public function boot(): void
    {
        Route::prefix('api/v1/auth/social')
            ->middleware('api')
            ->group(base_path('routes/domains/social.php'));
    }
}
