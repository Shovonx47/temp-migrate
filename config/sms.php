<?php

return [
    'default' => env('SMS_GATEWAY', 'log'),

    'drivers' => [
        'log' => [
            'title' => 'Log',
            'crendentials' => [],
            'config' => [
                'filename' => [
                    'required' => true,
                ],
            ]
        ],
        'revesms' => [
            'title' => 'Reve SMS',
            'credentials' => [
                'key' => [
                    'required' => true,
                    'password' => true
                ],
                'secret' => [
                    'required' => true,
                    'password' => true
                ]
            ]
        ]
    ]
];
