<?php

use App\Enums\DeliveryOutcome;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Drivers\QSmsDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// fetchDeliveryStatus
// ---------------------------------------------------------------------------

test('returns Delivered outcome for DELIVRD status', function () {
    Http::fake([
        '*/api/v2/MessageStatus*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => [
                'MobileNumber' => '7894561230',
                'SenderId' => 'test',
                'Message' => 'hi',
                'SubmitDate' => '16-Mar-2018 05:12:28 PM',
                'MessageId' => 'abc-123',
                'DoneDate' => '16-Mar-2018 05:11:00 PM',
                'Status' => 'DELIVRD',
            ],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered)
        ->and($result->rawStatus)->toBe('DELIVRD')
        ->and($result->deliveredAt)->not->toBeNull();
});

test('returns InTransit outcome for SENT status', function () {
    Http::fake([
        '*/api/v2/MessageStatus*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => [
                'MessageId' => 'abc-123',
                'Status' => 'SENT',
                'DoneDate' => '',
            ],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->rawStatus)->toBe('SENT');
});

test('returns InTransit outcome for ACCEPTD status', function () {
    Http::fake([
        '*/api/v2/MessageStatus*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => ['MessageId' => 'abc-123', 'Status' => 'ACCEPTD', 'DoneDate' => ''],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit);
});

test('returns Failed outcome for BLACKLIST status', function () {
    Http::fake([
        '*/api/v2/MessageStatus*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => [
                'MessageId' => 'abc-123',
                'Status' => 'BLACKLIST',
                'DoneDate' => '19-Mar-2018 05:48:36 PM',
            ],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed)
        ->and($result->rawStatus)->toBe('BLACKLIST');
});

test('returns Failed outcome for REJECTD status', function () {
    Http::fake([
        '*/api/v2/MessageStatus*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => ['MessageId' => 'abc-123', 'Status' => 'REJECTD', 'DoneDate' => ''],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed);
});

test('returns InTransit when gateway returns a non-zero ErrorCode', function () {
    Http::fake([
        '*/api/v2/MessageStatus*' => Http::response([
            'ErrorCode' => 41,
            'ErrorDescription' => 'Report Not Found.',
            'Data' => null,
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->errorMessage)->toContain('Report Not Found');
});

test('returns InTransit when connection fails', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->fetchDeliveryStatus('abc-123');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->errorMessage)->toContain('Connection failed');
});

// ---------------------------------------------------------------------------
// send() — gateway_message_id capture (bug fix)
// ---------------------------------------------------------------------------

test('send captures MessageId from Data array', function () {
    Http::fake([
        '*/api/v2/SendSMS*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => [
                ['MobileNumber' => '7894561230', 'MessageId' => '3bb944ad-d943-419f-83dc-f979002a8c0b'],
            ],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->send(new SmsRequest(to: '7894561230', body: 'Hi', from: 'Sender'));

    expect($result->successful)->toBeTrue()
        ->and($result->gatewayMessageId)->toBe('3bb944ad-d943-419f-83dc-f979002a8c0b');
});

// ---------------------------------------------------------------------------
// sendBulk() — correct Data[] parsing (bug fix)
// ---------------------------------------------------------------------------

test('sendBulk maps per-recipient MessageIds from Data array in recipient order', function () {
    $recipients = ['7894561230', '7894561231'];

    Http::fake([
        '*/api/v2/SendSMS*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => [
                ['MobileNumber' => '7894561230', 'MessageId' => 'id-aaa'],
                ['MobileNumber' => '7894561231', 'MessageId' => 'id-bbb'],
            ],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->sendBulk(new BulkSmsRequest(recipients: $recipients, body: 'Hi', from: 'Sender'));

    expect($result->successful)->toBeTrue()
        ->and($result->perRecipientIds)->toBe(['id-aaa', 'id-bbb']);
});

test('sendBulk returns null slot for a recipient not present in gateway Data response', function () {
    $recipients = ['7894561230', '7894561231'];

    Http::fake([
        '*/api/v2/SendSMS*' => Http::response([
            'ErrorCode' => 0,
            'ErrorDescription' => 'Success',
            'Data' => [
                // Second recipient missing from gateway response
                ['MobileNumber' => '7894561230', 'MessageId' => 'id-aaa'],
            ],
        ]),
    ]);

    $driver = new QSmsDriver(['url' => 'https://qsms.test', 'api_key' => 'key', 'client_id' => 'cid']);
    $result = $driver->sendBulk(new BulkSmsRequest(recipients: $recipients, body: 'Hi', from: 'Sender'));

    expect($result->successful)->toBeTrue()
        ->and($result->perRecipientIds)->toBe(['id-aaa', null]);
});
