<?php

namespace App\Domains\Billing\DTOs;

use App\Domains\Billing\Enums\PaymentGateway;
use App\Domains\Billing\Enums\PaymentStatus;

class PaymentResponseDTO
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $success,
        public PaymentGateway $gateway,
        public string $reference,
        public PaymentStatus $status,
        public ?string $redirectUrl = null,
        public ?string $pollUrl = null,
        public ?string $message = null,
        public ?string $instructions = null,
        public array $raw = [],
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'gateway' => $this->gateway->value,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'redirect_url' => $this->redirectUrl,
            'poll_url' => $this->pollUrl,
            'message' => $this->message,
            'instructions' => $this->instructions,
        ];
    }
}
