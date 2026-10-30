<?php

namespace App\Domains\Billing\Services;

use App\Core\Exceptions\DomainException;
use App\Domains\Billing\DTOs\PaymentRequestDTO;
use App\Domains\Billing\DTOs\PaymentResponseDTO;
use App\Domains\Billing\Models\Payment;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(protected PaymentManager $manager) {}

    public function initiate(PaymentRequestDTO $request, ?int $userId = null): PaymentResponseDTO
    {
        if (empty($request->reference)) {
            $request->reference = $this->generateReference();
        }

        $response = $this->manager->driver($request->gateway)->initiate($request);

        Payment::query()->create([
            'user_id' => $userId,
            'gateway' => $request->gateway,
            'reference' => $response->reference,
            'poll_url' => $response->pollUrl,
            'redirect_url' => $response->redirectUrl,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => $response->status,
            'email' => $request->email,
            'phone' => $request->phone,
            'method' => $request->method,
            'description' => $request->description,
            'metadata' => $request->metadata,
        ]);

        return $response;
    }

    public function refreshStatus(string $reference, ?int $userId = null): Payment
    {
        $payment = Payment::query()->where('reference', $reference)->first()
            ?? throw new DomainException('Payment not found.', 404);

        if ($userId !== null && $payment->user_id !== null && $payment->user_id !== $userId) {
            throw new DomainException('Payment not found.', 404);
        }

        $status = $this->manager->driver($payment->gateway)
            ->checkStatus($payment->reference, $payment->poll_url);

        if ($payment->status !== $status) {
            $payment->update(['status' => $status]);
        }

        return $payment->fresh();
    }

    protected function generateReference(): string
    {
        return 'PAY-'.strtoupper(Str::random(12));
    }
}
