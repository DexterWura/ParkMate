<?php

namespace App\Domains\Subscription\Enums;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case Starter = 'starter';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public function stripePriceId(): ?string
    {
        return match ($this) {
            self::Free => null,
            self::Starter => config('billing.plans.starter.price_id'),
            self::Pro => config('billing.plans.pro.price_id'),
            self::Enterprise => config('billing.plans.enterprise.price_id'),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Starter => 'Starter',
            self::Pro => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }
}
