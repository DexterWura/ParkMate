<?php

namespace App\Domains\Billing\DTOs;

use App\Domains\Billing\Enums\PaymentGateway;
use App\Shared\DTOs\BaseDTO;

class PaymentRequestDTO extends BaseDTO
{
    public PaymentGateway $gateway;

    public float $amount;

    public string $currency = 'USD';

    public string $email;

    public string $reference;

    public ?string $description = null;

    public ?string $phone = null;

    public ?string $method = null;

    public ?string $returnUrl = null;

    public ?string $resultUrl = null;

    /** @var array<string, mixed> */
    public array $metadata = [];

    public function isMobile(): bool
    {
        return ! empty($this->phone) && ! empty($this->method);
    }
}
