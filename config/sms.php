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

        'anbernet' => [
            'title' => 'Anbernet',
            'credentials' => [
                'base_url' => [
                    'label' => 'Base URL',
                    'placeholder' => 'https://rapi.anbernet.com:9978',
                    'helper' => 'RAPI: https://rapi.anbernet.com:9978 or WAPI: https://wapi.anbernet.com:9956',
                    'required' => true,
                ],
                'account' => [
                    'label' => 'Account',
                    'placeholder' => 'Enter your account name',
                    'required' => true,
                ],
                'password' => [
                    'label' => 'Password',
                    'placeholder' => 'Enter your account password',
                    'required' => true,
                    'password' => true,
                ],
                'token' => [
                    'label' => 'Token',
                    'placeholder' => 'Enter your API token',
                    'required' => true,
                    'password' => true,
                ],
            ],
        ],

        'infozillion' => [
            'title' => 'InfoZillion (MNPSP)',
            'credentials' => [
                'base_url' => [
                    'label' => 'Base URL',
                    'placeholder' => 'https://a2papiintl.mnpspbd.com',
                    'helper' => 'MNO URL: https://a2papiintl.mnpspbd.com or IPTSP URL: https://a2papiintl.mnpspbd.com',
                    'required' => true,
                ],
                'api_type' => [
                    'label' => 'API Type',
                    'type' => 'select',
                    'options' => [
                        'mno' => 'MNO',
                        'iptsp' => 'IPTSP',
                    ],
                    'helper' => 'MNO: uses /a2p-sms/ endpoints (GP, RB, TT, BL). IPTSP: uses /a2p-sms-iptsp/ endpoints (ADN, MN, BN, etc.).',
                    'required' => true,
                ],
                'username' => [
                    'label' => 'Username',
                    'placeholder' => 'Enter your aggregator username at MNO',
                    'required' => true,
                ],
                'password' => [
                    'label' => 'Password',
                    'placeholder' => 'Enter your aggregator password at MNO',
                    'required' => true,
                    'password' => true,
                ],
                'api_key' => [
                    'label' => 'API Key',
                    'placeholder' => 'Enter your MNPSP API key',
                    'required' => true,
                    'password' => true,
                ],
                'enabled_carriers' => [
                    'label' => 'Enabled Carriers',
                    'type' => 'multi_select',
                    'helper' => 'Select which carriers to check balances for. Leave empty to enable all.',
                ],
                'username_secondary' => [
                    'label' => 'Secondary Username',
                    'type' => 'hidden',
                ],
                'password_secondary' => [
                    'label' => 'Secondary Password',
                    'type' => 'hidden',
                ],
                'transaction_type' => [
                    'label' => 'Transaction Type',
                    'type' => 'hidden',
                ],
                'message_type' => [
                    'label' => 'Message Type',
                    'type' => 'hidden',
                ],
                'is_long_sms' => [
                    'label' => 'Long SMS',
                    'type' => 'hidden',
                ],
                'campaign_id' => [
                    'label' => 'Campaign ID',
                    'type' => 'hidden',
                ],
            ],
        ],
    ],
];
