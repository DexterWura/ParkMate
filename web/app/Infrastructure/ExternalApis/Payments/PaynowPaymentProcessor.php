<?php

namespace App\Infrastructure\ExternalApis\Payments;

use App\Domains\Billing\DTOs\PaymentRequestDTO;
use App\Domains\Billing\DTOs\PaymentResponseDTO;
use App\Domains\Billing\Enums\PaymentGateway;
use App\Domains\Billing\Enums\PaymentStatus;
use App\Shared\Contracts\PaymentProcessorInterface;
use Illuminate\Support\Facades\Http;

/**
 * Paynow (Zimbabwe) integration implementing the documented hash-based protocol.
 *
 * @see https://developers.paynow.co.zw
 */
class PaynowPaymentProcessor implements PaymentProcessorInterface
{
    protected string $initiateUrl = 'https://www.paynow.co.zw/interface/initiatetransaction';

    protected string $mobileUrl = 'https://www.paynow.co.zw/interface/remotetransaction';

    public function __construct(
        protected string $integrationId,
        protected string $integrationKey,
        protected string $resultUrl,
        protected string $returnUrl,
    ) {}

    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Paynow;
    }

    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO
    {
        $fields = [
            'id' => $this->integrationId,
            'reference' => $request->reference,
            'amount' => number_format($request->amount, 2, '.', ''),
            'additionalinfo' => $request->description ?: 'Payment',
            'returnurl' => $request->returnUrl ?: $this->returnUrl,
            'resulturl' => $request->resultUrl ?: $this->resultUrl,
            'authemail' => $request->email,
            'status' => 'Message',
        ];

        $isMobile = $request->isMobile();

        if ($isMobile) {
            $fields['phone'] = $request->phone;
            $fields['method'] = $request->method;
        }

        $fields['hash'] = $this->generateHash($fields);

        $response = Http::asForm()->post(
            $isMobile ? $this->mobileUrl : $this->initiateUrl,
            $fields
        );

        if ($response->failed()) {
            return $this->failure($request, 'Paynow request failed.');
        }

        parse_str($response->body(), $data);

        if (($data['status'] ?? '') === 'Error' || ! isset($data['status'])) {
            return $this->failure($request, $data['error'] ?? 'Paynow returned an error.', $data);
        }

        return new PaymentResponseDTO(
            success: true,
            gateway: $this->gateway(),
            reference: $request->reference,
            status: PaymentStatus::Pending,
            redirectUrl: $data['browserurl'] ?? null,
            pollUrl: $data['pollurl'] ?? null,
            message: $isMobile
                ? 'Approve the payment prompt on your phone.'
                : 'Redirect the customer to complete payment.',
            instructions: $data['instructions'] ?? null,
            raw: $data,
        );
    }

    public function checkStatus(string $reference, ?string $pollUrl = null): PaymentStatus
    {
        if (! $pollUrl) {
            return PaymentStatus::Pending;
        }

        $response = Http::asForm()->post($pollUrl);

        if ($response->failed()) {
            return PaymentStatus::Pending;
        }

        parse_str($response->body(), $data);

        return $this->mapStatus($data['status'] ?? '');
    }

    protected function mapStatus(string $status): PaymentStatus
    {
        return match (strtolower($status)) {
            'paid', 'awaiting delivery', 'delivered' => PaymentStatus::Paid,
            'cancelled' => PaymentStatus::Cancelled,
            'failed', 'disputed', 'refunded' => PaymentStatus::Failed,
            'sent', 'created' => PaymentStatus::Processing,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * Paynow hash: uppercase SHA512 of the concatenated field values + integration key.
     *
     * @param  array<string, string>  $fields
     */
    protected function generateHash(array $fields): string
    {
        $concat = '';

        foreach ($fields as $key => $value) {
            if (strtolower($key) === 'hash') {
                continue;
            }
            $concat .= $value;
        }

        $concat .= $this->integrationKey;

        return strtoupper(hash('sha512', $concat));
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected function failure(PaymentRequestDTO $request, string $message, array $raw = []): PaymentResponseDTO
    {
        return new PaymentResponseDTO(
            success: false,
            gateway: $this->gateway(),
            reference: $request->reference,
            status: PaymentStatus::Failed,
            message: $message,
            raw: $raw,
        );
    }
}
