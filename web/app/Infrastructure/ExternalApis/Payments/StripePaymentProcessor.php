<?php

namespace App\Infrastructure\ExternalApis\Payments;

use App\Domains\Billing\DTOs\PaymentRequestDTO;
use App\Domains\Billing\DTOs\PaymentResponseDTO;
use App\Domains\Billing\Enums\PaymentGateway;
use App\Domains\Billing\Enums\PaymentStatus;
use App\Shared\Contracts\PaymentProcessorInterface;
use Illuminate\Support\Facades\Http;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    protected string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct(
        protected string $secret,
        protected string $successUrl,
        protected string $cancelUrl,
    ) {}

    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Stripe;
    }

    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO
    {
        $response = Http::asForm()
            ->withToken($this->secret)
            ->post("{$this->baseUrl}/checkout/sessions", [
                'mode' => 'payment',
                'success_url' => $request->returnUrl ?: $this->successUrl,
                'cancel_url' => $this->cancelUrl,
                'client_reference_id' => $request->reference,
                'customer_email' => $request->email,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => strtolower($request->currency),
                'line_items[0][price_data][unit_amount]' => (int) round($request->amount * 100),
                'line_items[0][price_data][product_data][name]' => $request->description ?: 'Payment',
                'metadata[reference]' => $request->reference,
            ]);

        if ($response->failed()) {
            return new PaymentResponseDTO(
                success: false,
                gateway: $this->gateway(),
                reference: $request->reference,
                status: PaymentStatus::Failed,
                message: $response->json('error.message') ?? 'Stripe checkout failed.',
                raw: $response->json() ?? [],
            );
        }

        return new PaymentResponseDTO(
            success: true,
            gateway: $this->gateway(),
            reference: $request->reference,
            status: PaymentStatus::Pending,
            redirectUrl: $response->json('url'),
            pollUrl: $response->json('id'),
            message: 'Redirect the customer to complete payment.',
            raw: $response->json() ?? [],
        );
    }

    public function checkStatus(string $reference, ?string $pollUrl = null): PaymentStatus
    {
        if (! $pollUrl) {
            return PaymentStatus::Pending;
        }

        $response = Http::withToken($this->secret)
            ->get("{$this->baseUrl}/checkout/sessions/{$pollUrl}");

        if ($response->failed()) {
            return PaymentStatus::Pending;
        }

        return match ($response->json('payment_status')) {
            'paid' => PaymentStatus::Paid,
            'unpaid' => PaymentStatus::Pending,
            'no_payment_required' => PaymentStatus::Paid,
            default => PaymentStatus::Pending,
        };
    }
}
