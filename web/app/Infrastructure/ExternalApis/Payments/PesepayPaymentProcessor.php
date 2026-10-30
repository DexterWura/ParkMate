<?php

namespace App\Infrastructure\ExternalApis\Payments;

use App\Domains\Billing\DTOs\PaymentRequestDTO;
use App\Domains\Billing\DTOs\PaymentResponseDTO;
use App\Domains\Billing\Enums\PaymentGateway;
use App\Domains\Billing\Enums\PaymentStatus;
use App\Shared\Contracts\PaymentProcessorInterface;
use Illuminate\Support\Facades\Http;

/**
 * Pesepay (Zimbabwe) integration using AES-256-CBC encrypted payloads.
 *
 * @see https://developers.pesepay.com
 */
class PesepayPaymentProcessor implements PaymentProcessorInterface
{
    protected string $baseUrl = 'https://api.pesepay.com/api/payments-engine';

    public function __construct(
        protected string $integrationKey,
        protected string $encryptionKey,
        protected string $resultUrl,
        protected string $returnUrl,
    ) {}

    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Pesepay;
    }

    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO
    {
        $payload = [
            'amountDetails' => [
                'amount' => $request->amount,
                'currencyCode' => $request->currency,
            ],
            'reasonForPayment' => $request->description ?: 'Payment',
            'resultUrl' => $request->resultUrl ?: $this->resultUrl,
            'returnUrl' => $request->returnUrl ?: $this->returnUrl,
            'merchantReference' => $request->reference,
            'customer' => [
                'email' => $request->email,
                'phoneNumber' => $request->phone,
            ],
        ];

        $response = Http::withHeaders([
            'authorization' => $this->integrationKey,
            'content-type' => 'application/json',
        ])->post("{$this->baseUrl}/v2/payments/make-payment", [
            'payload' => $this->encrypt($payload),
        ]);

        if ($response->failed()) {
            return $this->failure($request, $response->json('message') ?? 'Pesepay request failed.');
        }

        $data = $this->decrypt($response->json('payload'));

        if (! $data || empty($data['redirectUrl'])) {
            return $this->failure($request, $data['message'] ?? 'Pesepay did not return a redirect URL.', $data ?? []);
        }

        return new PaymentResponseDTO(
            success: true,
            gateway: $this->gateway(),
            reference: $data['referenceNumber'] ?? $request->reference,
            status: PaymentStatus::Pending,
            redirectUrl: $data['redirectUrl'] ?? null,
            pollUrl: $data['pollUrl'] ?? ($data['referenceNumber'] ?? null),
            message: 'Redirect the customer to complete payment.',
            raw: $data,
        );
    }

    public function checkStatus(string $reference, ?string $pollUrl = null): PaymentStatus
    {
        $lookup = $pollUrl ?: $reference;

        $response = Http::withHeaders([
            'authorization' => $this->integrationKey,
        ])->get("{$this->baseUrl}/v1/payments/check-payment", [
            'referenceNumber' => $lookup,
        ]);

        if ($response->failed()) {
            return PaymentStatus::Pending;
        }

        $data = $this->decrypt($response->json('payload'));

        if (! $data) {
            return PaymentStatus::Pending;
        }

        if (($data['transactionStatus'] ?? null) === 'SUCCESS' || ($data['paid'] ?? false) === true) {
            return PaymentStatus::Paid;
        }

        return $this->mapStatus($data['transactionStatus'] ?? '');
    }

    protected function mapStatus(string $status): PaymentStatus
    {
        return match (strtoupper($status)) {
            'SUCCESS' => PaymentStatus::Paid,
            'CANCELLED' => PaymentStatus::Cancelled,
            'FAILED', 'ERROR', 'DECLINED', 'CLOSED', 'TIME_OUT' => PaymentStatus::Failed,
            'PROCESSING', 'PENDING', 'AUTHORIZATION_REQUESTED' => PaymentStatus::Processing,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function encrypt(array $data): string
    {
        $iv = substr($this->encryptionKey, 0, 16);

        $encrypted = openssl_encrypt(
            json_encode($data),
            'AES-256-CBC',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($encrypted);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decrypt(?string $payload): ?array
    {
        if (! $payload) {
            return null;
        }

        $iv = substr($this->encryptionKey, 0, 16);

        $decrypted = openssl_decrypt(
            base64_decode($payload),
            'AES-256-CBC',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            return null;
        }

        return json_decode($decrypted, true);
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
