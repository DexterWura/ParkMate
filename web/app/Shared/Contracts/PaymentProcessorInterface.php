<?php

namespace App\Shared\Contracts;

use App\Domains\Billing\DTOs\PaymentRequestDTO;
use App\Domains\Billing\DTOs\PaymentResponseDTO;
use App\Domains\Billing\Enums\PaymentGateway;
use App\Domains\Billing\Enums\PaymentStatus;

interface PaymentProcessorInterface
{
    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO;

    public function checkStatus(string $reference, ?string $pollUrl = null): PaymentStatus;

    public function gateway(): PaymentGateway;
}
