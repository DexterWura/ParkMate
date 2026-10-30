<?php

namespace App\Domains\User\Providers;

use App\Domains\User\Events\UserRegistered;
use App\Domains\User\Interfaces\UserRepositoryInterface;
use App\Domains\User\Listeners\SendEmailVerificationNotification;
use App\Domains\User\Models\User;
use App\Domains\User\Policies\UserPolicy;
use App\Domains\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(UserRegistered::class, SendEmailVerificationNotification::class);

        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        Route::prefix('api/v1/auth')
            ->middleware('api')
            ->group(base_path('routes/domains/user.php'));
    }
}
