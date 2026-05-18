<?php

use App\Enums\DeliveryOutcome;
use App\Services\Sms\Drivers\ReveSmsDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('returns Delivered outcome when Status is 0 (DELIVRD)', function () {
    Http::fake([
        '*/getstatus*' => Http::response([
            'Status' => '0',
            'Text' => 'DELIVRD',
            'Message_ID' => '31094106',
            'Delivery Time' => '1728272684675',
        ]),
    ]);

    $driver = new ReveSmsDriver(['url' => 'https://reve.test', 'key' => 'key', 'secret' => 'secret']);
    $result = $driver->fetchDeliveryStatus('31094106');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered)
        ->and($result->rawStatus)->toBe('DELIVRD')
        ->and($result->deliveredAt)->not->toBeNull()
        ->and($result->deliveredAt->timestamp)->toBe(1728272684);
});

test('returns InTransit outcome when Status is 4 (SENT)', function () {
    Http::fake([
        '*/getstatus*' => Http::response([
            'Status' => '4',
            'Text' => 'SENT',
            'Message_ID' => '31094113',
            'Delivery Time' => '1728276267503',
        ]),
    ]);

    $driver = new ReveSmsDriver(['url' => 'https://reve.test', 'key' => 'key', 'secret' => 'secret']);
    $result = $driver->fetchDeliveryStatus('31094113');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->rawStatus)->toBe('SENT');
});

test('returns Failed outcome when Status is non-zero and non-four', function () {
    Http::fake([
        '*/getstatus*' => Http::response([
            'Status' => '109',
            'Text' => 'REJECTD',
            'Message_ID' => '',
            'Delivery Time' => '0',
        ]),
    ]);

    $driver = new ReveSmsDriver(['url' => 'https://reve.test', 'key' => 'key', 'secret' => 'secret']);
    $result = $driver->fetchDeliveryStatus('missing-id');

    expect($result->outcome)->toBe(DeliveryOutcome::Failed)
        ->and($result->rawStatus)->toBe('REJECTD')
        ->and($result->errorMessage)->toBe('REJECTD');
});

test('returns InTransit when HTTP request fails', function () {
    Http::fake(['*/getstatus*' => Http::response(null, 500)]);

    $driver = new ReveSmsDriver(['url' => 'https://reve.test', 'key' => 'key', 'secret' => 'secret']);
    $result = $driver->fetchDeliveryStatus('any-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit);
});

test('returns InTransit when connection fails', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $driver = new ReveSmsDriver(['url' => 'https://reve.test', 'key' => 'key', 'secret' => 'secret']);
    $result = $driver->fetchDeliveryStatus('any-id');

    expect($result->outcome)->toBe(DeliveryOutcome::InTransit)
        ->and($result->errorMessage)->toContain('Connection failed');
});

test('handles zero Delivery Time gracefully (no deliveredAt)', function () {
    Http::fake([
        '*/getstatus*' => Http::response([
            'Status' => '0',
            'Text' => 'DELIVRD',
            'Message_ID' => '12345',
            'Delivery Time' => '0',
        ]),
    ]);

    $driver = new ReveSmsDriver(['url' => 'https://reve.test', 'key' => 'key', 'secret' => 'secret']);
    $result = $driver->fetchDeliveryStatus('12345');

    expect($result->outcome)->toBe(DeliveryOutcome::Delivered)
        ->and($result->deliveredAt)->toBeNull();
});
