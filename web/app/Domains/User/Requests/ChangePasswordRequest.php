<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $min = (int) config('boilerplate.auth.password_min_length', 8);

        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min($min)],
        ];
    }
}
