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
                    'placeholder' => 'mno',
                    'helper' => 'MNO: uses /a2p-sms/ endpoints. IPTSP: uses /a2p-sms-iptsp/ endpoints.',
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
                'bill_msisdn' => [
                    'label' => 'Bill MSISDN',
                    'placeholder' => '8801XXXXXXXXX',
                    'helper' => 'Your registered billing MSISDN at the MNO platform.',
                    'required' => true,
                ],
                'api_key' => [
                    'label' => 'API Key',
                    'placeholder' => 'Enter your MNPSP API key',
                    'required' => true,
                    'password' => true,
                ],
                'username_secondary' => [
                    'label' => 'Secondary Username',
                    'placeholder' => 'Optional retry username',
                    'helper' => 'Used as retry option if primary credentials fail.',
                    'required' => false,
                ],
                'password_secondary' => [
                    'label' => 'Secondary Password',
                    'placeholder' => 'Optional retry password',
                    'required' => false,
                    'password' => true,
                ],
                'bill_msisdn_secondary' => [
                    'label' => 'Secondary Bill MSISDN',
                    'placeholder' => 'Optional retry MSISDN',
                    'required' => false,
                ],
                'transaction_type' => [
                    'label' => 'Transaction Type',
                    'placeholder' => 'T',
                    'helper' => 'T for Transactional, P for Promotional.',
                    'required' => true,
                ],
                'message_type' => [
                    'label' => 'Message Type',
                    'placeholder' => '1',
                    'helper' => '1: English Text, 2: English Flash, 3: Unicode Bangla, 4: Unicode Flash Bangla.',
                    'required' => true,
                ],
                'is_long_sms' => [
                    'label' => 'Long SMS',
                    'placeholder' => 'false',
                    'helper' => 'Set to true for messages exceeding 160 characters.',
                    'required' => false,
                ],
                'campaign_id' => [
                    'label' => 'Campaign ID',
                    'placeholder' => 'Optional for Transactional, required for Promotional',
                    'required' => false,
                ],
            ],
        ],
    ],
];
