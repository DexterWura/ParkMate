<?php

namespace App\Domains\Billing\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Failed, self::Cancelled], true);
    }

    public function isSuccessful(): bool
    {
        return $this === self::Paid;
    }
}
