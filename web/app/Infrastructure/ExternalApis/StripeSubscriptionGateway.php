<?php

namespace App\Infrastructure\ExternalApis;

use App\Domains\Subscription\Enums\SubscriptionPlan;
use App\Domains\User\Models\User;
use App\Shared\Contracts\SubscriptionGatewayInterface;

class StripeSubscriptionGateway implements SubscriptionGatewayInterface
{
    public function createCustomer(User $user, array $options = []): string
    {
        if ($user->hasStripeId()) {
            return $user->stripe_id;
        }

        $user->createAsStripeCustomer($options);

        return $user->stripe_id;
    }

    public function createCheckoutSession(User $user, string $priceId, array $options = []): string
    {
        $this->createCustomer($user);

        $checkout = $user->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout(array_merge([
                'success_url' => config('billing.checkout.success_url'),
                'cancel_url' => config('billing.checkout.cancel_url'),
            ], $options));

        return $checkout->url;
    }

    public function cancelSubscription(User $user): void
    {
        $user->subscription('default')?->cancel();
    }

    public function resumeSubscription(User $user): void
    {
        $user->subscription('default')?->resume();
    }

    public function swapPlan(User $user, SubscriptionPlan $plan): void
    {
        $priceId = $plan->stripePriceId();

        if (! $priceId) {
            $user->subscription('default')?->cancel();

            return;
        }

        $subscription = $user->subscription('default');

        if ($subscription) {
            $subscription->swap($priceId);

            return;
        }

        $user->newSubscription('default', $priceId)->create();
    }
}
