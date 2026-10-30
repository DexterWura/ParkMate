<?php

namespace App\Domains\Subscription\Services;

use App\Domains\Subscription\Enums\SubscriptionPlan;
use App\Domains\User\Models\User;

class SubscriptionService
{
    public function currentPlan(User $user): SubscriptionPlan
    {
        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return SubscriptionPlan::Free;
        }

        $priceId = $subscription->stripe_price;

        foreach (SubscriptionPlan::cases() as $plan) {
            if ($plan->stripePriceId() === $priceId) {
                return $plan;
            }
        }

        return SubscriptionPlan::Free;
    }

    public function isSubscribed(User $user): bool
    {
        return $user->subscribed('default');
    }

    public function onPlan(User $user, SubscriptionPlan $plan): bool
    {
        return $this->currentPlan($user) === $plan;
    }
}
