<?php

namespace App\Domains\Billing\Controllers;

use App\Domains\Billing\DTOs\PaymentRequestDTO;
use App\Domains\Billing\Enums\PaymentGateway;
use App\Domains\Billing\Requests\InitiatePaymentRequest;
use App\Domains\Billing\Services\PaymentService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function __construct(protected PaymentService $payments) {}

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $dto = new PaymentRequestDTO;
        $dto->gateway = PaymentGateway::from($data['gateway']);
        $dto->amount = (float) $data['amount'];
        $dto->currency = strtoupper($data['currency'] ?? 'USD');
        $dto->email = $data['email'];
        $dto->reference = '';
        $dto->description = $data['description'] ?? null;
        $dto->phone = $data['phone'] ?? null;
        $dto->method = $data['method'] ?? null;
        $dto->returnUrl = $data['return_url'] ?? null;
        $dto->metadata = $data['metadata'] ?? [];

        $response = $this->payments->initiate($dto, $request->user()?->id);

        if (! $response->success) {
            return $this->error($response->message ?? 'Payment failed.', 422, $response->toArray());
        }

        return $this->success($response->toArray(), 'Payment initiated.');
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = $this->payments->refreshStatus($reference, $request->user()?->id);

        return $this->success([
            'reference' => $payment->reference,
            'gateway' => $payment->gateway->value,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ]);
    }
}
