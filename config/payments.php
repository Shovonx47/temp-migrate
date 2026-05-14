<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment driver. Supported: "piprapay",
    | "log". The log driver is useful for local development and testing.
    |
    */

    'default' => env('PAYMENT_DRIVER', 'piprapay'),

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers
    |--------------------------------------------------------------------------
    */

    'drivers' => [

        'piprapay' => [
            'api_key' => env('PIPRAPAY_API_KEY'),
            'sandbox' => env('PIPRAPAY_SANDBOX', true),
            'sandbox_url' => 'https://sandbox.piprapay.com',
            'live_url' => 'https://piprapay.com',
        ],

        'log' => [
            //
        ],

    ],

];
