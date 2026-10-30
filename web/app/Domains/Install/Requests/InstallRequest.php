<?php

namespace App\Domains\Install\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class InstallRequest extends FormRequest
{
    public function authorize(): bool
    {
        $installationIsPublic = true;

        return $installationIsPublic;
    }

    public function rules(): array
    {
        $mysql = Rule::requiredIf(fn () => in_array($this->input('db_connection'), ['mysql', 'mariadb'], true));

        return [
            'app_name' => ['required', 'string', 'max:255'],
            'app_url' => ['required', 'url:http,https', 'max:2048'],
            'db_connection' => ['required', Rule::in(['sqlite', 'mysql', 'mariadb'])],
            'db_host' => [$mysql, 'nullable', 'string', 'not_regex:/[;\r\n]/', 'max:255'],
            'db_port' => [$mysql, 'nullable', 'integer', 'between:1,65535'],
            'db_database' => [$mysql, 'nullable', 'string', 'regex:/\A[A-Za-z_][A-Za-z0-9_]*\z/', 'max:64'],
            'db_username' => [$mysql, 'nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:4096'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255'],
            'owner_password' => ['required', 'confirmed', Password::min((int) config('boilerplate.auth.password_min_length', 8))],
        ];
    }
}
