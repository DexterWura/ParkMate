<?php

namespace App\Domains\Billing\Services;

use App\Core\Exceptions\DomainException;
use App\Domains\Billing\Enums\PaymentGateway;
use App\Infrastructure\ExternalApis\Payments\PaynowPaymentProcessor;
use App\Infrastructure\ExternalApis\Payments\PesepayPaymentProcessor;
use App\Infrastructure\ExternalApis\Payments\StripePaymentProcessor;
use App\Shared\Contracts\PaymentProcessorInterface;
use Illuminate\Contracts\Container\Container;

class PaymentManager
{
    /** @var array<string, PaymentProcessorInterface> */
    protected array $resolved = [];

    public function __construct(protected Container $app) {}

    public function driver(PaymentGateway|string $gateway): PaymentProcessorInterface
    {
        $gateway = $gateway instanceof PaymentGateway
            ? $gateway
            : (PaymentGateway::tryFrom($gateway) ?? throw new DomainException("Unknown payment gateway: {$gateway}.", 422));

        return $this->resolved[$gateway->value] ??= $this->build($gateway);
    }

    protected function build(PaymentGateway $gateway): PaymentProcessorInterface
    {
        return match ($gateway) {
            PaymentGateway::Stripe => new StripePaymentProcessor(
                secret: (string) config('services.stripe.secret'),
                successUrl: (string) config('payments.stripe.success_url'),
                cancelUrl: (string) config('payments.stripe.cancel_url'),
            ),
            PaymentGateway::Paynow => new PaynowPaymentProcessor(
                integrationId: (string) config('payments.paynow.integration_id'),
                integrationKey: (string) config('payments.paynow.integration_key'),
                resultUrl: (string) config('payments.paynow.result_url'),
                returnUrl: (string) config('payments.paynow.return_url'),
            ),
            PaymentGateway::Pesepay => new PesepayPaymentProcessor(
                integrationKey: (string) config('payments.pesepay.integration_key'),
                encryptionKey: (string) config('payments.pesepay.encryption_key'),
                resultUrl: (string) config('payments.pesepay.result_url'),
                returnUrl: (string) config('payments.pesepay.return_url'),
            ),
        };
    }
}
