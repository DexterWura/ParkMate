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

        // Avoid forcing APP_URL during first-run install — a production APP_URL in
        // .env.example would otherwise break installer CSS/JS on localhost.
        $installed = is_file(storage_path('app/installed'));
        $appUrl = env('APP_URL');

        if (
            $installed
            && ! $this->app->runningUnitTests()
            && is_string($appUrl)
            && $appUrl !== ''
        ) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        if ($installed && config('boilerplate.security.force_https')) {
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }
}
