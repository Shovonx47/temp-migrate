<?php

return [
    'default' => env('SMS_GATEWAY', 'log'),

    'drivers' => [
        'log' => [
            'title' => 'Log',
            'credentials' => [],
            'config' => [
                'filename' => [
                    'label' => 'Log Filename',
                    'placeholder' => 'Enter log filename (optional)',
                    'required' => false,
                ],
            ],
        ],
        'revesms' => [
            'title' => 'Reve SMS',
            'credentials' => [
                'url' => [
                    'label' => 'Base URL',
                    'placeholder' => 'https://example.com:8888',
                    'helper' => 'Enter only the base URL, without any endpoint path such as /sendtext.',
                    'required' => true,
                ],
                'key' => [
                    'label' => 'API Key',
                    'placeholder' => 'Enter your api key',
                    'required' => true,
                    'password' => true,
                ],
                'secret' => [
                    'label' => 'Secret Key',
                    'placeholder' => 'Enter your secret key',
                    'required' => true,
                    'password' => true,
                ],
            ],
        ],
    ],
];
