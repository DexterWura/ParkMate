<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Domain modules and their service provider classes.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    protected array $domains = [
        \App\Domains\Install\Providers\InstallServiceProvider::class,
        \App\Domains\User\Providers\UserServiceProvider::class,
        \App\Domains\SocialAccounts\Providers\SocialAccountsServiceProvider::class,
        \App\Domains\Billing\Providers\BillingServiceProvider::class,
        \App\Domains\Subscription\Providers\SubscriptionServiceProvider::class,
        \App\Domains\Settings\Providers\SettingsServiceProvider::class,
        \App\Domains\Parking\Providers\ParkingServiceProvider::class,
        \App\Domains\Admin\Providers\AdminServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->domains as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        //
    }
}
