<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS rate
    |--------------------------------------------------------------------------
    |
    | Per-SMS-part rate charged to a user when their own `users.sms_rate`
    | column is null. Stored in the user's currency (BDT).
    |
    */
    'default_sms_rate' => env('BILLING_DEFAULT_SMS_RATE', 0.50),
];
