<?php

namespace App\Domains\Billing\Requests;

use App\Domains\Billing\Enums\PaymentGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => ['required', Rule::enum(PaymentGateway::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'email' => ['required', 'email'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'method' => ['sometimes', 'nullable', 'string', 'max:50'],
            'return_url' => ['sometimes', 'nullable', 'url'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
