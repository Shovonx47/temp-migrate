<?php

use App\Enums\DeliveryOutcome;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Drivers\InfozillionDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Send SMS tests
|--------------------------------------------------------------------------
*/

test('send returns successful response with gateway message id', function () {
    Http::fake([
        '*/a2p-sms/api/v1/send-sms' => Http::response([
            'serverTxnId' => '2f637717-3de4-4b15-8d69-c794e3e97f9e',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'mnoTxnId' => 'GP 20220327185030-01729024313-1233123233',
            'mnoResponseCode' => '1000',
            'mnoResponseMessage' => 'Success!',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801720102673',
        body: 'Dear Subscriber, please recharge tk.100',
        from: '22222',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('2f637717-3de4-4b15-8d69-c794e3e97f9e');
});

test('send handles integer serverResponseCode from API', function () {
    Http::fake([
        '*/a2p-sms/api/v1/send-sms' => Http::response([
            'serverTxnId' => '07339d8b-da8f-440f-9bd0-8504db0c4e73',
            'serverResponseCode' => 9000,
            'serverResponseMessage' => 'Request successful!',
            'mnoTxnId' => '8183178448560021362',
            'mnoResponseCode' => '1000',
            'mnoResponseMessage' => 'Success',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801720102673',
        body: 'Test message',
        from: '22222',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('07339d8b-da8f-440f-9bd0-8504db0c4e73');
});

test('send returns failure when serverResponseCode is not 9000', function () {
    Http::fake([
        '*/a2p-sms/api/v1/send-sms' => Http::response([
            'serverTxnId' => '53e38c84-e81d-43e4-b2a3-a356d19b3e06',
            'serverResponseCode' => '9001',
            'serverResponseMessage' => 'Required field missing! : apiKey',
            'mnoTxnId' => null,
            'mnoResponseCode' => null,
            'mnoResponseMessage' => null,
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => '',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801720102673',
        body: 'Test',
        from: '22222',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toBe('Required field missing! : apiKey');
});

test('send returns failure on connection error', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801720102673',
        body: 'Test',
        from: '22222',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toContain('Connection failed');
});

test('send uses IPTSP endpoint when api_type is iptsp', function () {
    Http::fake([
        '*/a2p-sms-iptsp/api/v1/send-sms' => Http::response([
            'serverTxnId' => 'iptsp-txn-id',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'iptsp',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801720102673',
        body: 'Test',
        from: '22222',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('iptsp-txn-id');
});

test('send includes secondary credentials when provided', function () {
    Http::fake([
        '*/a2p-sms/api/v1/send-sms' => Http::response([
            'serverTxnId' => 'uuid-with-secondary',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'username_secondary' => 'sec_user',
        'password_secondary' => 'sec_pass',
        'bill_msisdn_secondary' => '8801620587635',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801720102673',
        body: 'Test',
        from: '22222',
    ));

    expect($result->successful)->toBeTrue();

    Http::assertSent(function ($request) {
        $body = $request->data();

        return ($body['usernameSecondary'] ?? '') === 'sec_user'
            && ($body['passwordSecondary'] ?? '') === 'sec_pass'
            && ($body['billMsisdnSecondary'] ?? '') === '8801620587635';
    });
});

/*
|--------------------------------------------------------------------------
| Bulk SMS tests
|--------------------------------------------------------------------------
*/

test('sendBulk returns batch id on success', function () {
    Http::fake([
        '*/a2p-sms/api/v1/send-sms' => Http::response([
            'serverTxnId' => 'bulk-batch-id-001',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'mnoTxnId' => null,
            'mnoResponseCode' => null,
            'mnoResponseMessage' => null,
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'P',
        'message_type' => '1',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['8801720102673', '8801720102674', '8801720102675'],
        body: 'Promotional message',
        from: '22222',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayBatchId)->toBe('bulk-batch-id-001');
});

test('sendBulk returns failure on error response', function () {
    Http::fake([
        '*/a2p-sms/api/v1/send-sms' => Http::response([
            'serverTxnId' => 'txn-id',
            'serverResponseCode' => '9003',
            'serverResponseMessage' => 'Out of balance!',
            'mnoTxnId' => null,
            'mnoResponseCode' => null,
            'mnoResponseMessage' => null,
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'P',
        'message_type' => '1',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['8801720102673'],
        body: 'Test',
        from: '22222',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toBe('Out of balance!');
});

test('sendBulk returns failure on connection error', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
        'transaction_type' => 'T',
        'message_type' => '1',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['8801720102673'],
        body: 'Test',
        from: '22222',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toContain('Connection failed');
});

/*
|--------------------------------------------------------------------------
| Delivery status tests
|--------------------------------------------------------------------------
*/

test('returns Delivered outcome when deliveryStatus contains Delivered', function () {
    Http::fake([
        '*/a2p-proxy-api/api/v1/check-delivery-report' => Http::response([
            'serverTxnId' => '2f637717-3de4-4b15-8d69-c794e3e97f9e',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'a2pDeliveryStatus' => 'Request successful!',
            'a2pSendSmsBusinessCode' => '9000',
            'deliveryStatus' => ['8801720102673-Delivered'],
            'dndMsisdn' => null,
            'invalidMsisdn' => null,
            'ansSendSmsHttpStatus' => '200 OK',
            'ansSendSmsBusinessCode' => '1000',
            'mnoResponseCode' => '1000',
            'mnoResponseMessage' => 'success',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('2f637717-3de4-4b15-8d69-c794e3e97f9e');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered)
        ->and($result->rawStatus)->toBe('8801720102673-Delivered');
});

test('returns Failed outcome when deliveryStatus contains UnDelivered', function () {
    Http::fake([
        '*/a2p-proxy-api/api/v1/check-delivery-report' => Http::response([
            'serverTxnId' => '2f637717-3de4-4b15-8d69-c794e3e97f9e',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'a2pDeliveryStatus' => 'Request successful!',
            'a2pSendSmsBusinessCode' => '9000',
            'deliveryStatus' => ['8801720102674-UnDelivered'],
            'dndMsisdn' => null,
            'invalidMsisdn' => null,
            'ansSendSmsHttpStatus' => '200 OK',
            'ansSendSmsBusinessCode' => '1000',
            'mnoResponseCode' => '1000',
            'mnoResponseMessage' => 'success',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('2f637717-3de4-4b15-8d69-c794e3e97f9e');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed)
        ->and($result->rawStatus)->toBe('8801720102674-UnDelivered');
});

test('returns InTransit when deliveryStatus is empty and no failure indicator', function () {
    Http::fake([
        '*/a2p-proxy-api/api/v1/check-delivery-report' => Http::response([
            'serverTxnId' => 'txn-id',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'a2pDeliveryStatus' => 'Request successful!',
            'a2pSendSmsBusinessCode' => '9000',
            'deliveryStatus' => [],
            'dndMsisdn' => null,
            'invalidMsisdn' => null,
            'ansSendSmsHttpStatus' => '200 OK',
            'ansSendSmsBusinessCode' => '1000',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('txn-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit);
});

test('returns Failed when a2pDeliveryStatus indicates server failure', function () {
    Http::fake([
        '*/a2p-proxy-api/api/v1/check-delivery-report' => Http::response([
            'serverTxnId' => 'txn-id',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'a2pDeliveryStatus' => 'MNO server failure!',
            'a2pSendSmsBusinessCode' => '9011',
            'deliveryStatus' => [],
            'dndMsisdn' => null,
            'invalidMsisdn' => null,
            'ansSendSmsHttpStatus' => '502 BAD_GATEWAY',
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('txn-id');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed)
        ->and($result->rawStatus)->toBe('MNO server failure!');
});

test('returns InTransit when server response code is not 9000', function () {
    Http::fake([
        '*/a2p-proxy-api/api/v1/check-delivery-report' => Http::response([
            'serverTxnId' => 'txn-id',
            'serverResponseCode' => '9016',
            'serverResponseMessage' => 'Invalid check delivery request!',
            'a2pDeliveryStatus' => null,
            'a2pSendSmsBusinessCode' => null,
            'deliveryStatus' => null,
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('txn-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->errorMessage)->toBe('Invalid check delivery request!');
});

test('returns InTransit when connection fails', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'mno',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('any-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->errorMessage)->toContain('Connection failed');
});

test('delivery check uses IPTSP endpoint when api_type is iptsp', function () {
    Http::fake([
        '*/a2p-proxy-api-iptsp/api/v1/check-delivery-report' => Http::response([
            'serverTxnId' => 'txn-id',
            'serverResponseCode' => '9000',
            'serverResponseMessage' => 'Request successful!',
            'a2pDeliveryStatus' => 'Request successful!',
            'deliveryStatus' => ['8801720102673-Delivered'],
        ]),
    ]);

    $driver = new InfozillionDriver([
        'base_url' => 'https://a2papiintl.mnpspbd.com',
        'api_type' => 'iptsp',
        'username' => 'test_user',
        'password' => 'test_pass',
        'bill_msisdn' => '8801526589635',
        'api_key' => 'test_api_key',
    ]);

    $result = $driver->fetchDeliveryStatus('txn-id');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered);
});
