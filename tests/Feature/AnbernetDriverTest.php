<?php

use App\Enums\DeliveryOutcome;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Drivers\AnbernetDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Send SMS tests
|--------------------------------------------------------------------------
*/

test('send normalizes recipient with leading plus sign', function () {
    Http::fake([
        '*/api/v1/sendsms' => function (Request $request) {
            expect($request['receivers'])->toBe(['8801760505055']);

            return Http::response([
                'status' => 'success',
                'account' => 'test_account',
                'total' => 1,
                'messageids' => [
                    ['receiver' => '8801760505055', 'messageid' => '550e8400-e29b-41d4-a716-446655440000'],
                ],
            ]);
        },
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '+8801760505055',
        body: 'Hello from Anbernet',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('550e8400-e29b-41d4-a716-446655440000');
});

test('send recognizes uppercase SUCCESS status and Status key', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            'Status' => 'SUCCESS',
            'account' => 'test_account',
            'total' => 1,
            'messageids' => [
                ['receiver' => '8801760505055', 'messageid' => '550e8400-e29b-41d4-a716-446655440000'],
            ],
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '+8801760505055',
        body: 'Hello from Anbernet',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('550e8400-e29b-41d4-a716-446655440000');
});

test('send handles array-wrapped response with status and http code', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            [
                'status' => 'success',
                'account' => 'Softcents',
                'smstype' => 'queued',
                'total' => 1,
                'success_count' => 1,
                'failed_count' => 0,
                'messageids' => [
                    ['receiver' => '8801734544744', 'messageid' => '96d84f31-27b1-4b67-be9d-ab2758cab457'],
                ],
            ],
            200,
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'Softcents',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '+8801734544744',
        body: 'hi from system',
        from: '8809639900600',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('96d84f31-27b1-4b67-be9d-ab2758cab457');
});

test('sendBulk handles array-wrapped response with status and http code', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            [
                'status' => 'success',
                'account' => 'Softcents',
                'smstype' => 'queued',
                'total' => 1,
                'success_count' => 1,
                'failed_count' => 0,
                'messageids' => [
                    ['receiver' => '8801734544744', 'messageid' => '96d84f31-27b1-4b67-be9d-ab2758cab457'],
                ],
            ],
            200,
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'Softcents',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['+8801734544744'],
        body: 'hi from system',
        from: '8809639900600',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->perRecipientIds)->toBe(['96d84f31-27b1-4b67-be9d-ab2758cab457']);
});

test('send returns failure on wrong credentials', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            'message' => 'Wrong credentials',
        ], 401),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'wrong',
        'password' => 'wrong',
        'token' => 'wrong',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801760505055',
        body: 'Test',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toBe('Wrong credentials');
});

test('send returns failure on connection error', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test',
        'password' => 'test',
        'token' => 'test',
    ]);

    $result = $driver->send(new SmsRequest(
        to: '8801760505055',
        body: 'Test',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toContain('Connection failed');
});

/*
|--------------------------------------------------------------------------
| Bulk SMS tests
|--------------------------------------------------------------------------
*/

test('sendBulk maps per-recipient message IDs from response', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            'status' => 'success',
            'account' => 'test_account',
            'total' => 2,
            'messageids' => [
                ['receiver' => '8801760505055', 'messageid' => 'uuid-1'],
                ['receiver' => '8801777706703', 'messageid' => 'uuid-2'],
            ],
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['8801760505055', '8801777706703'],
        body: 'Bulk message',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->perRecipientIds)->toBe(['uuid-1', 'uuid-2']);
});

test('sendBulk returns null slot for recipient not in gateway response', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            'status' => 'success',
            'account' => 'test_account',
            'total' => 1,
            'messageids' => [
                ['receiver' => '8801760505055', 'messageid' => 'uuid-1'],
            ],
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['8801760505055', '8801777706703'],
        body: 'Bulk message',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->perRecipientIds)->toBe(['uuid-1', null]);
});

test('sendBulk returns failure on error response', function () {
    Http::fake([
        '*/api/v1/sendsms' => Http::response([
            'message' => 'Wrong credentials',
        ], 401),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'wrong',
        'password' => 'wrong',
        'token' => 'wrong',
    ]);

    $result = $driver->sendBulk(new BulkSmsRequest(
        recipients: ['8801760505055'],
        body: 'Test',
        from: 'TestSID',
    ));

    expect($result->successful)->toBeFalse()
        ->and($result->errorMessage)->toBe('Wrong credentials');
});

/*
|--------------------------------------------------------------------------
| Delivery status tests
|--------------------------------------------------------------------------
*/

test('returns Delivered outcome when status is DELIVERED', function () {
    Http::fake([
        '*/api/v1/dlrcheck' => Http::response([
            'dlrurl' => '550e8400-e29b-41d4-a716-446655440000',
            'receiver' => '8801760505055',
            'status' => 'DELIVERED',
            'datetime' => '2024-01-15 14:30:25',
            'smscid' => 'smscid_12345',
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('550e8400-e29b-41d4-a716-446655440000');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered)
        ->and($result->rawStatus)->toBe('DELIVERED')
        ->and($result->deliveredAt)->not->toBeNull()
        ->and($result->deliveredAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 14:30:25');
});

test('returns InTransit outcome when status is ACCEPTD', function () {
    Http::fake([
        '*/api/v1/dlrcheck' => Http::response([
            'dlrurl' => 'uuid-1',
            'receiver' => '8801760505055',
            'status' => 'ACCEPTD',
            'datetime' => '',
            'smscid' => '',
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('uuid-1');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->rawStatus)->toBe('ACCEPTD');
});

test('returns InTransit outcome when status is PENDING', function () {
    Http::fake([
        '*/api/v1/dlrcheck' => Http::response([
            'dlrurl' => 'uuid-1',
            'receiver' => '8801760505055',
            'status' => 'PENDING',
            'datetime' => '',
            'smscid' => '',
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('uuid-1');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->rawStatus)->toBe('PENDING');
});

test('returns Failed outcome when status is FAILED', function () {
    Http::fake([
        '*/api/v1/dlrcheck' => Http::response([
            'dlrurl' => 'uuid-1',
            'receiver' => '8801760505055',
            'status' => 'FAILED',
            'datetime' => '',
            'smscid' => '',
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('uuid-1');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed)
        ->and($result->rawStatus)->toBe('FAILED')
        ->and($result->errorMessage)->toBe('FAILED');
});

test('returns Failed outcome when status is NOT_FOUND', function () {
    Http::fake([
        '*/api/v1/dlrcheck' => Http::response([
            'status' => 'NOT_FOUND',
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('nonexistent-id');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed)
        ->and($result->rawStatus)->toBe('NOT_FOUND')
        ->and($result->errorMessage)->toBe('NOT_FOUND');
});

test('returns InTransit when HTTP request fails', function () {
    Http::fake(['*/api/v1/dlrcheck' => Http::response(null, 500)]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('any-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit);
});

test('returns InTransit when connection fails', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('any-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->errorMessage)->toContain('Connection failed');
});

test('handles empty datetime gracefully (no deliveredAt)', function () {
    Http::fake([
        '*/api/v1/dlrcheck' => Http::response([
            'dlrurl' => 'uuid-1',
            'receiver' => '8801760505055',
            'status' => 'DELIVERED',
            'datetime' => '',
            'smscid' => '',
        ]),
    ]);

    $driver = new AnbernetDriver([
        'base_url' => 'https://rapi.anbernet.com:9978',
        'account' => 'test_account',
        'password' => 'test_pass',
        'token' => 'test_token',
    ]);

    $result = $driver->fetchDeliveryStatus('uuid-1');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered)
        ->and($result->deliveredAt)->toBeNull();
});
