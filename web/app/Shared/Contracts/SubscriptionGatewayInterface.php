<?php

namespace App\Shared\Contracts;

use App\Domains\User\Models\User;

/**
 * Subscription billing gateway (Cashier/Stripe subscriptions).
 * Separate from one-off payment processors (Stripe/Paynow/Pesepay).
 */
interface SubscriptionGatewayInterface
{
    public function createCustomer(User $user, array $options = []): string;

    public function createCheckoutSession(User $user, string $priceId, array $options = []): string;

    public function cancelSubscription(User $user): void;

    public function resumeSubscription(User $user): void;
}
