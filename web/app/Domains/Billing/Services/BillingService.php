<?php

namespace App\Domains\Billing\Services;

use App\Core\Exceptions\DomainException;
use App\Domains\Subscription\Enums\SubscriptionPlan;
use App\Domains\User\Models\User;
use App\Infrastructure\ExternalApis\StripeSubscriptionGateway;
use App\Shared\Contracts\SubscriptionGatewayInterface;

class BillingService
{
    public function __construct(
        protected SubscriptionGatewayInterface $gateway
    ) {}

    public function checkout(User $user, SubscriptionPlan $plan): string
    {
        $priceId = $plan->stripePriceId();

        if (! $priceId) {
            throw new DomainException('This plan does not require checkout.', 400);
        }

        return $this->gateway->createCheckoutSession($user, $priceId);
    }

    public function portalUrl(User $user): string
    {
        return $user->billingPortalUrl(config('billing.portal.return_url'));
    }

    public function cancel(User $user): void
    {
        $this->gateway->cancelSubscription($user);
    }

    public function resume(User $user): void
    {
        $this->gateway->resumeSubscription($user);
    }

    public function swap(User $user, SubscriptionPlan $plan): void
    {
        if ($this->gateway instanceof StripeSubscriptionGateway) {
            $this->gateway->swapPlan($user, $plan);
        }
    }
}
