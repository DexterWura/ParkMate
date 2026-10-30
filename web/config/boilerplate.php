<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Boilerplate Configuration
    |--------------------------------------------------------------------------
    |
    | Central place to customize behavior without touching core code.
    | Override these in your .env or publish to config/boilerplate.php.
    |
    */

    'name' => env('APP_NAME', 'OpenLot'),

    'api' => [
        'prefix' => 'api/v1',
        'rate_limit' => env('API_RATE_LIMIT', 60),
        'auth_rate_limit' => env('API_AUTH_RATE_LIMIT', 10),
    ],

    'auth' => [
        'require_email_verification' => env('AUTH_REQUIRE_EMAIL_VERIFICATION', true),
        'password_min_length' => env('AUTH_PASSWORD_MIN', 8),
        'token_expiration_minutes' => env('SANCTUM_TOKEN_EXPIRATION', null),
    ],

    'security' => [
        'force_https' => env('FORCE_HTTPS', false),
        'security_headers' => env('SECURITY_HEADERS', true),
    ],

    'features' => [
        'google_auth' => env('FEATURE_GOOGLE_AUTH', true),
        'subscriptions' => env('FEATURE_SUBSCRIPTIONS', true),
        'payments' => env('FEATURE_PAYMENTS', true),
    ],

];
