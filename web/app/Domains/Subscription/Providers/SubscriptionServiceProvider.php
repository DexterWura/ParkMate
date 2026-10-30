<?php

namespace App\Domains\Subscription\Providers;

use App\Domains\Subscription\Services\SubscriptionService;
use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SubscriptionService::class);
    }

    public function boot(): void
    {
        //
    }
}
