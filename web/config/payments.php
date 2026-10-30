<?php

return [

    'default' => env('PAYMENTS_DEFAULT_GATEWAY', 'stripe'),

    'stripe' => [
        'success_url' => env('PAYMENTS_STRIPE_SUCCESS_URL', env('APP_URL').'/payments/success'),
        'cancel_url' => env('PAYMENTS_STRIPE_CANCEL_URL', env('APP_URL').'/payments/cancel'),
    ],

    'paynow' => [
        'integration_id' => env('PAYNOW_INTEGRATION_ID'),
        'integration_key' => env('PAYNOW_INTEGRATION_KEY'),
        'result_url' => env('PAYNOW_RESULT_URL', env('APP_URL').'/api/v1/payments/paynow/callback'),
        'return_url' => env('PAYNOW_RETURN_URL', env('APP_URL').'/payments/success'),
    ],

    'pesepay' => [
        'integration_key' => env('PESEPAY_INTEGRATION_KEY'),
        'encryption_key' => env('PESEPAY_ENCRYPTION_KEY'),
        'result_url' => env('PESEPAY_RESULT_URL', env('APP_URL').'/api/v1/payments/pesepay/callback'),
        'return_url' => env('PESEPAY_RETURN_URL', env('APP_URL').'/payments/success'),
    ],

];
