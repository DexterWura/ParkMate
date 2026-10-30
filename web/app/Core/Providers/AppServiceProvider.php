<?php

namespace App\Core\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.openlot');
        Paginator::defaultSimpleView('vendor.pagination.openlot');

        $appUrl = env('APP_URL');

        if (
            ! $this->app->runningUnitTests()
            && is_string($appUrl)
            && $appUrl !== ''
        ) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        if (config('boilerplate.security.force_https')) {
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }
}
