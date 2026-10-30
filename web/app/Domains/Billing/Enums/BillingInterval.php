<?php

namespace App\Domains\Billing\Enums;

enum BillingInterval: string
{
    case Monthly = 'month';
    case Yearly = 'year';
}
