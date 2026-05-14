<?php

return [
    'default' => env('PAYMENT_DRIVER', 'piprapy'),

    'drivers' => [
        'piprapy' => [
            'url' => env('PIPRAPAY_BASE_URL'),
            'key' => env('PIPRAPAY_API_KEY'),
        ],
    ],
];
