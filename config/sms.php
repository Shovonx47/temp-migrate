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
                'base_url' => [
                    'label' => 'Base URL',
                    'placeholder' => 'https://example.com:8888',
                    'helper' => 'Enter only the base URL, without any endpoint path such as /sendtext.',
                    'required' => true,
                ],
                'api_key' => [
                    'label' => 'API Key',
                    'placeholder' => 'Enter your API key',
                    'required' => true,
                    'password' => true,
                ],
                'secret_key' => [
                    'label' => 'Secret Key',
                    'placeholder' => 'Enter your secret key',
                    'required' => true,
                    'password' => true,
                ],
            ],
        ],

        'qsms' => [
            'title' => 'QSMS',
            'credentials' => [
                'base_url' => [
                    'label' => 'Base URL',
                    'placeholder' => 'https://api.qsms.com',
                    'helper' => 'Enter only the base URL, without any endpoint path such as /api/v2/SendSMS.',
                    'required' => true,
                ],
                'api_key' => [
                    'label' => 'API Key',
                    'placeholder' => 'Enter your API key',
                    'required' => true,
                    'password' => true,
                ],
                'client_id' => [
                    'label' => 'Client ID',
                    'placeholder' => 'Enter your client ID',
                    'required' => true,
                    'password' => true,
                ],
            ],
        ],
    ],
];
