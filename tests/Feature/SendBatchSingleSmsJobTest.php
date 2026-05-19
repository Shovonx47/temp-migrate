<?php

use App\Contracts\SmsGateway;
use App\Enums\GatewayDriver;
use App\Enums\MessageStatus;
use App\Enums\SenderType;
use App\Enums\TransactionDirection;
use App\Jobs\SendBatchSingleSmsJob;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Batch;
use App\Models\Sender;
use App\Models\User;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;
use App\Support\Facades\Sms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a MessageBatch wired to a Gateway via a Sender.
 */
function makeSingleSendableBatch(
    string $body = 'Hello {name}',
    SenderType $senderType = SenderType::Numeric,
): Batch {
    $user = User::factory()->create();

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Log,
        'is_enabled' => true,
        'supports_bulk' => false,
        'requests_per_minute' => 60,
        'config' => [],
    ]);

    $sender = Sender::factory()->create([
        'gateway_id' => $gateway->id,
        'type' => $senderType,
        'is_enabled' => true,
        'sender_id' => 'TestSender',
    ]);

    return Batch::factory()
        ->for($user)
        ->create([
            'sender_id' => $sender->id,
            'body' => $body,
            'status' => 'completed',
        ]);
}

/** Insert a single pending Message row and return its ID. */
function insertSinglePendingMessage(Batch $batch, string $to = '+8801711000001', string $body = 'Hello John'): int
{
    $now = now();
    Message::insert([[
        'user_id' => $batch->user_id,
        'message_batch_id' => $batch->id,
        'campaign_id' => null,
        'sender_id' => $batch->sender_id,
        'to' => $to,
        'body' => $body,
        'status' => MessageStatus::Pending->value,
        'parts' => 1,
        'rate' => 0.30,
        'cost' => 0.30,
        'created_at' => $now,
        'updated_at' => $now,
    ]]);

    return (int) Message::where('message_batch_id', $batch->id)
        ->where('to', $to)
        ->orderByDesc('id')
        ->value('id');
}

function runSingleJob(Batch $batch, int $messageId): void
{
    (new SendBatchSingleSmsJob($batch, $messageId))->handle();
}

// ---------------------------------------------------------------------------
// Success cases
// ---------------------------------------------------------------------------

test('marks message sent with gateway message ID', function () {
    $batch = makeSingleSendableBatch('Hello {name}');
    $messageId = insertSinglePendingMessage($batch, body: 'Hello John');

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true, gatewayMessageId: 'gw-123');
            }
        };
    });

    runSingleJob($batch, $messageId);

    $message = Message::find($messageId);

    expect($message->status)->toBe(MessageStatus::Sent)
        ->and($message->sent_at)->not->toBeNull()
        ->and($message->gateway_message_id)->toBe('gw-123')
        ->and($message->failed_at)->toBeNull()
        ->and($message->failed_reason)->toBeNull()
        ->and($message->poll_attempt)->toBe(1)
        ->and($message->next_poll_at)->not->toBeNull()
        ->and($message->next_poll_at->isFuture())->toBeTrue();
});

test('does not set poll columns when gateway returns no message ID', function () {
    $batch = makeSingleSendableBatch('Hello {name}');
    $messageId = insertSinglePendingMessage($batch, body: 'Hello John');

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true, gatewayMessageId: null);
            }
        };
    });

    runSingleJob($batch, $messageId);

    $message = Message::find($messageId);

    expect($message->status)->toBe(MessageStatus::Sent)
        ->and($message->poll_attempt)->toBeNull()
        ->and($message->next_poll_at)->toBeNull();
});

test('marks message failed when gateway returns unsuccessful response', function () {
    $batch = makeSingleSendableBatch();
    $messageId = insertSinglePendingMessage($batch);

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: false, errorMessage: 'Invalid number');
            }
        };
    });

    runSingleJob($batch, $messageId);

    $message = Message::find($messageId);

    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failed_at)->not->toBeNull()
        ->and($message->failed_reason)->toBe('Invalid number')
        ->and($message->sent_at)->toBeNull();
});

test('marks message failed when driver throws an exception', function () {
    $batch = makeSingleSendableBatch();
    $messageId = insertSinglePendingMessage($batch);

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                throw new RuntimeException('Network timeout');
            }
        };
    });

    runSingleJob($batch, $messageId);

    $message = Message::find($messageId);

    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failed_reason)->toBe('Network timeout')
        ->and($message->sent_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// Refund cases
// ---------------------------------------------------------------------------

test('refunds message cost to user balance when gateway returns unsuccessful response', function () {
    $batch = makeSingleSendableBatch();
    $user = User::find($batch->user_id);
    $balanceBefore = (float) $user->balance;
    $messageId = insertSinglePendingMessage($batch); // cost = 0.30

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: false, errorMessage: 'Invalid number');
            }
        };
    });

    runSingleJob($batch, $messageId);

    $message = Message::find($messageId);
    $user->refresh();

    expect($user->balance)->toBe(round($balanceBefore + $message->cost, 4))
        ->and($message->transactions()->where('direction', TransactionDirection::Credit)->count())->toBe(1);
});

test('refunds message cost to user balance when driver throws an exception', function () {
    $batch = makeSingleSendableBatch();
    $user = User::find($batch->user_id);
    $balanceBefore = (float) $user->balance;
    $messageId = insertSinglePendingMessage($batch); // cost = 0.30

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                throw new RuntimeException('Network timeout');
            }
        };
    });

    runSingleJob($batch, $messageId);

    $message = Message::find($messageId);
    $user->refresh();

    expect($user->balance)->toBe(round($balanceBefore + $message->cost, 4));
});

test('does not create a refund transaction when message cost is zero', function () {
    $batch = makeSingleSendableBatch();
    $user = User::find($batch->user_id);
    $balanceBefore = (float) $user->balance;

    // Insert a zero-cost message.
    $now = now();
    Message::insert([[
        'user_id' => $batch->user_id,
        'message_batch_id' => $batch->id,
        'campaign_id' => null,
        'sender_id' => $batch->sender_id,
        'to' => '+8801711000009',
        'body' => 'Hello',
        'status' => MessageStatus::Pending->value,
        'parts' => 1,
        'rate' => 0.0,
        'cost' => 0.0,
        'created_at' => $now,
        'updated_at' => $now,
    ]]);
    $messageId = (int) Message::where('message_batch_id', $batch->id)->value('id');

    Sms::extend('log', fn () => new class implements SmsGateway
    {
        public function send(SmsRequest $r): SmsResponse
        {
            return new SmsResponse(successful: false, errorMessage: 'Rejected');
        }
    });

    runSingleJob($batch, $messageId);

    $user->refresh();

    expect($user->balance)->toBe($balanceBefore)
        ->and(Message::find($messageId)->transactions()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Guard / idempotency cases
// ---------------------------------------------------------------------------

test('does nothing when message is already sent (idempotency guard)', function () {
    $batch = makeSingleSendableBatch();
    $messageId = insertSinglePendingMessage($batch);

    // Pre-mark as sent.
    Message::where('id', $messageId)->update(['status' => MessageStatus::Sent->value]);

    $called = false;
    Sms::extend('log', function () use (&$called) {
        return new class($called) implements SmsGateway
        {
            public function __construct(private bool &$called) {}

            public function send(SmsRequest $r): SmsResponse
            {
                $this->called = true;

                return new SmsResponse(successful: true);
            }
        };
    });

    runSingleJob($batch, $messageId);

    expect($called)->toBeFalse()
        ->and(Message::find($messageId)->status)->toBe(MessageStatus::Sent);
});

test('does nothing when batch has no sender configured', function () {
    $user = User::factory()->create();
    $batch = Batch::factory()->for($user)->create(['sender_id' => null]);
    $messageId = insertSinglePendingMessage($batch);

    runSingleJob($batch, $messageId);

    expect(Message::find($messageId)->status)->toBe(MessageStatus::Pending);
});

test('does nothing when message ID does not exist', function () {
    $batch = makeSingleSendableBatch();

    // Should not throw.
    runSingleJob($batch, 99999);

    expect(Message::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Queue / dispatch
// ---------------------------------------------------------------------------

test('SendBatchSingleSmsJob is dispatchable and carries the batch and message ID', function () {
    Queue::fake();

    $batch = makeSingleSendableBatch();

    SendBatchSingleSmsJob::dispatch($batch, 42)->onQueue('sms-sending');

    Queue::assertPushed(SendBatchSingleSmsJob::class, fn ($job) => $job->batch->id === $batch->id
        && $job->messageId === 42);
});
