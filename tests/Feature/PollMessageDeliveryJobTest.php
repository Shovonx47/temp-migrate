<?php

use App\Contracts\SmsGateway;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\DeliveryOutcome;
use App\Enums\GatewayDriver;
use App\Enums\MessageStatus;
use App\Enums\TransactionDirection;
use App\Jobs\PollMessageDeliveryJob;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Services\Sms\Data\DeliveryStatusResponse;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;
use App\Support\Facades\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * @param  array<string, mixed>  $messageOverrides
 */
function makeSentMessageWithDriver(
    DeliveryStatusResponse $dlrResponse,
    array $messageOverrides = [],
): Message {
    $user = User::factory()->create();

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Log,
        'is_enabled' => true,
        'supports_bulk' => false,
        'requests_per_minute' => 60,
        'credentials' => [],
    ]);

    $sender = Sender::factory()->create([
        'gateway_id' => $gateway->id,
        'is_enabled' => true,
    ]);

    // Register a fake Log driver that also implements SupportsDeliveryStatus.
    Sms::extend('log', function () use ($dlrResponse) {
        return new class($dlrResponse) implements ShouldQueue, SmsGateway, SupportsDeliveryStatus
        {
            public function __construct(private readonly DeliveryStatusResponse $dlrResponse) {}

            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true);
            }

            public function fetchDeliveryStatus(string $gatewayMessageId, ?string $recipient = null): DeliveryStatusResponse
            {
                return $this->dlrResponse;
            }
        };
    });

    $message = Message::factory()
        ->for($user)
        ->state(array_merge([
            'campaign_id' => null, // avoid CampaignFactory which depends on missing migrations
            'sender_id' => $sender->id,
            'status' => MessageStatus::Sent,
            'gateway_message_id' => 'gw-msg-001',
            'sent_at' => now(),
            'poll_attempt' => 1,
            'next_poll_at' => now()->subMinute(), // already due
        ], $messageOverrides))
        ->create();

    return $message;
}

// ---------------------------------------------------------------------------
// Delivered outcome
// ---------------------------------------------------------------------------

test('marks message as Delivered and sets delivered_at when DLR returns Delivered', function () {
    $deliveredAt = Carbon::now()->subMinutes(1);

    $message = makeSentMessageWithDriver(new DeliveryStatusResponse(
        outcome: DeliveryOutcome::Delivered,
        rawStatus: 'DELIVRD',
        deliveredAt: $deliveredAt,
    ));

    (new PollMessageDeliveryJob($message->id))->handle();

    $fresh = $message->fresh();

    expect($fresh->status)->toBe(MessageStatus::Delivered)
        ->and($fresh->delivered_at)->not->toBeNull()
        ->and($fresh->delivered_at->timestamp)->toBe($deliveredAt->timestamp)
        ->and($fresh->poll_attempt)->toBeNull()
        ->and($fresh->next_poll_at)->toBeNull();
});

test('uses current time as delivered_at when DLR returns Delivered without a timestamp', function () {
    $message = makeSentMessageWithDriver(new DeliveryStatusResponse(
        outcome: DeliveryOutcome::Delivered,
        deliveredAt: null,
    ));

    (new PollMessageDeliveryJob($message->id))->handle();

    $fresh = $message->fresh();

    expect($fresh->status)->toBe(MessageStatus::Delivered)
        ->and($fresh->delivered_at)->not->toBeNull()
        ->and($fresh->poll_attempt)->toBeNull()
        ->and($fresh->next_poll_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// Failed outcome
// ---------------------------------------------------------------------------

test('marks message as Failed when DLR returns Failed', function () {
    $message = makeSentMessageWithDriver(new DeliveryStatusResponse(
        outcome: DeliveryOutcome::Failed,
        rawStatus: 'BLACKLIST',
        errorMessage: 'BLACKLIST',
    ));

    (new PollMessageDeliveryJob($message->id))->handle();

    $fresh = $message->fresh();

    expect($fresh->status)->toBe(MessageStatus::Failed)
        ->and($fresh->failed_at)->not->toBeNull()
        ->and($fresh->failed_reason)->toBe('BLACKLIST')
        ->and($fresh->poll_attempt)->toBeNull()
        ->and($fresh->next_poll_at)->toBeNull();
});

test('refunds message cost to user balance when DLR returns Failed', function () {
    $messageCost = 0.50;

    $message = makeSentMessageWithDriver(
        new DeliveryStatusResponse(outcome: DeliveryOutcome::Failed, rawStatus: 'BLACKLIST', errorMessage: 'BLACKLIST'),
        ['cost' => $messageCost],
    );

    $user = User::find($message->user_id);
    $balanceBefore = (float) $user->balance;

    (new PollMessageDeliveryJob($message->id))->handle();

    $user->refresh();

    expect($user->balance)->toBe(round($balanceBefore + $messageCost, 4))
        ->and($message->fresh()->transactions()->where('direction', TransactionDirection::Credit)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// InTransit — DB-backed rescheduling
// ---------------------------------------------------------------------------

test('increments poll_attempt and sets next_poll_at when DLR returns InTransit and attempts remain', function () {
    $message = makeSentMessageWithDriver(
        new DeliveryStatusResponse(outcome: DeliveryOutcome::InTransit),
        ['poll_attempt' => 1],
    );

    (new PollMessageDeliveryJob($message->id))->handle();

    $fresh = $message->fresh();

    // Message should remain Sent.
    expect($fresh->status)->toBe(MessageStatus::Sent)
        ->and($fresh->poll_attempt)->toBe(2)
        ->and($fresh->next_poll_at)->not->toBeNull()
        ->and($fresh->next_poll_at->isFuture())->toBeTrue();
});

test('clears poll columns and stops when max attempts reached', function () {
    $message = makeSentMessageWithDriver(
        new DeliveryStatusResponse(outcome: DeliveryOutcome::InTransit),
        ['poll_attempt' => PollMessageDeliveryJob::MAX_ATTEMPTS],
    );

    (new PollMessageDeliveryJob($message->id))->handle();

    $fresh = $message->fresh();

    expect($fresh->status)->toBe(MessageStatus::Sent)
        ->and($fresh->poll_attempt)->toBeNull()
        ->and($fresh->next_poll_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// Guard cases
// ---------------------------------------------------------------------------

test('exits silently when message is not in Sent status', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->pending()->create(['campaign_id' => null]);

    (new PollMessageDeliveryJob($message->id))->handle();

    expect($message->fresh()->status)->toBe(MessageStatus::Pending);
});

test('exits silently when message has no gateway_message_id', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->sent()->create(['campaign_id' => null, 'gateway_message_id' => null]);

    (new PollMessageDeliveryJob($message->id))->handle();

    expect($message->fresh()->status)->toBe(MessageStatus::Sent);
});

test('clears poll columns when message was sent more than 48 hours ago', function () {
    $message = makeSentMessageWithDriver(
        new DeliveryStatusResponse(outcome: DeliveryOutcome::InTransit),
        ['sent_at' => now()->subHours(49), 'poll_attempt' => 1, 'next_poll_at' => now()->subMinute()],
    );

    (new PollMessageDeliveryJob($message->id))->handle();

    $fresh = $message->fresh();

    expect($fresh->status)->toBe(MessageStatus::Sent)
        ->and($fresh->poll_attempt)->toBeNull()
        ->and($fresh->next_poll_at)->toBeNull();
});

test('exits silently when driver does not implement SupportsDeliveryStatus', function () {
    $user = User::factory()->create();

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Log,
        'is_enabled' => true,
        'credentials' => [],
    ]);

    $sender = Sender::factory()->create(['gateway_id' => $gateway->id, 'is_enabled' => true]);

    // Plain driver with no DLR support.
    Sms::extend('log', fn () => new class implements SmsGateway
    {
        public function send(SmsRequest $r): SmsResponse
        {
            return new SmsResponse(successful: true);
        }
    });

    $message = Message::factory()
        ->for($user)
        ->sent()
        ->state(['campaign_id' => null, 'sender_id' => $sender->id, 'gateway_message_id' => 'gw-001'])
        ->create();

    (new PollMessageDeliveryJob($message->id))->handle();

    expect($message->fresh()->status)->toBe(MessageStatus::Sent);
});
