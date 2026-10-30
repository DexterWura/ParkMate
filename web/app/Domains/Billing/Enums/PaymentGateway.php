<?php

namespace App\Domains\Billing\Enums;

enum PaymentGateway: string
{
    case Stripe = 'stripe';
    case Paynow = 'paynow';
    case Pesepay = 'pesepay';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::Paynow => 'Paynow',
            self::Pesepay => 'Pesepay',
        };
    }
}
