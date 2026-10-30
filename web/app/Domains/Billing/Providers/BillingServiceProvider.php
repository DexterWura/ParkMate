<?php

namespace App\Domains\Billing\Providers;

use App\Domains\Billing\Services\BillingService;
use App\Domains\Billing\Services\PaymentManager;
use App\Domains\Billing\Services\PaymentService;
use App\Infrastructure\ExternalApis\StripeSubscriptionGateway;
use App\Shared\Contracts\SubscriptionGatewayInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionGatewayInterface::class, StripeSubscriptionGateway::class);
        $this->app->singleton(BillingService::class);

        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app));
        $this->app->singleton(PaymentService::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        Route::prefix('api/v1/billing')
            ->middleware(['api', 'auth:sanctum'])
            ->group(base_path('routes/domains/billing.php'));

        Route::prefix('api/v1/payments')
            ->middleware(['api', 'auth:sanctum'])
            ->group(base_path('routes/domains/payment.php'));

        Route::post(
            'stripe/webhook',
            [\App\Domains\Billing\Controllers\WebhookController::class, 'handleWebhook']
        )->middleware('api');
    }
}
