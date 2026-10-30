<?php

return [

    'checkout' => [
        'success_url' => env('BILLING_CHECKOUT_SUCCESS_URL', env('APP_URL').'/billing/success'),
        'cancel_url' => env('BILLING_CHECKOUT_CANCEL_URL', env('APP_URL').'/billing/cancel'),
    ],

    'portal' => [
        'return_url' => env('BILLING_PORTAL_RETURN_URL', env('APP_URL').'/settings/billing'),
    ],

    'plans' => [
        'starter' => [
            'price_id' => env('STRIPE_PRICE_STARTER'),
        ],
        'pro' => [
            'price_id' => env('STRIPE_PRICE_PRO'),
        ],
        'enterprise' => [
            'price_id' => env('STRIPE_PRICE_ENTERPRISE'),
        ],
    ],

];
