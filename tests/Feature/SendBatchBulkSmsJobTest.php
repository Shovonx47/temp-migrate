<?php

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\DeliveryOutcome;
use App\Enums\GatewayDriver;
use App\Enums\MessageStatus;
use App\Enums\SenderType;
use App\Enums\TransactionDirection;
use App\Jobs\SendBatchBulkSmsJob;
use App\Models\Batch;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Data\DeliveryStatusResponse;
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
 * Build a MessageBatch wired to a bulk-capable Gateway via a Sender.
 */
function makeBulkSendableBatch(
    string $body = 'Hello world',
    SenderType $senderType = SenderType::Numeric,
): Batch {
    $user = User::factory()->create();

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Log,
        'is_enabled' => true,
        'supports_bulk' => true,
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

/** Insert pending Message rows directly, bypassing the billing pipeline. */
function insertBulkPendingMessages(Batch $batch, int $count = 3, string $body = 'Hello world'): void
{
    $now = now();
    $rows = [];

    for ($i = 1; $i <= $count; $i++) {
        $rows[] = [
            'user_id' => $batch->user_id,
            'message_batch_id' => $batch->id,
            'campaign_id' => null,
            'sender_id' => $batch->sender_id,
            'to' => '+8801711000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'body' => $body,
            'status' => MessageStatus::Pending->value,
            'parts' => 1,
            'rate' => 0.30,
            'cost' => 0.30,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    Message::insert($rows);
}

/**
 * Run the job synchronously.
 *
 * @param  int[]  $messageIds
 */
function runBulkJob(Batch $batch, array $messageIds = []): void
{
    (new SendBatchBulkSmsJob($batch, $messageIds))->handle();
}

// ---------------------------------------------------------------------------
// Success cases
// ---------------------------------------------------------------------------

test('marks all messages sent with per-recipient gateway IDs', function () {
    $batch = makeBulkSendableBatch('No variables here');
    insertBulkPendingMessages($batch, 3);

    Sms::extend('log', function () {
        return new class implements SmsGateway, SupportsBulkSms
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                $ids = array_map(static fn ($i) => 'msg-'.$i, range(1, count($r->recipients)));

                return new BulkSmsResponse(successful: true, perRecipientIds: $ids);
            }
        };
    });

    $allIds = Message::where('message_batch_id', $batch->id)->orderBy('id')->pluck('id')->all();
    runBulkJob($batch, $allIds);

    $messages = Message::where('message_batch_id', $batch->id)->orderBy('id')->get();

    expect($messages)->toHaveCount(3)
        ->and($messages->every(fn ($m) => $m->status === MessageStatus::Sent))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->sent_at !== null))->toBeTrue()
        ->and($messages->pluck('gateway_message_id')->all())->toBe(['msg-1', 'msg-2', 'msg-3']);
});

test('marks all messages sent with shared batch ID when driver returns single ID', function () {
    $batch = makeBulkSendableBatch('No variables here');
    insertBulkPendingMessages($batch, 3);

    Sms::extend('log', function () {
        return new class implements SmsGateway, SupportsBulkSms
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                return new BulkSmsResponse(successful: true, gatewayBatchId: 'batch-xyz');
            }
        };
    });

    $allIds = Message::where('message_batch_id', $batch->id)->pluck('id')->all();
    runBulkJob($batch, $allIds);

    $messages = Message::where('message_batch_id', $batch->id)->get();

    expect($messages->every(fn ($m) => $m->status === MessageStatus::Sent))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->gateway_message_id === 'batch-xyz'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Failure cases
// ---------------------------------------------------------------------------

test('marks all messages failed when gateway returns unsuccessful response', function () {
    $batch = makeBulkSendableBatch('No variables here');
    insertBulkPendingMessages($batch, 2);

    Sms::extend('log', function () {
        return new class implements SmsGateway, SupportsBulkSms
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: false);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                return new BulkSmsResponse(successful: false, errorMessage: 'Rate limit exceeded');
            }
        };
    });

    $allIds = Message::where('message_batch_id', $batch->id)->pluck('id')->all();
    runBulkJob($batch, $allIds);

    $messages = Message::where('message_batch_id', $batch->id)->get();

    expect($messages->every(fn ($m) => $m->status === MessageStatus::Failed))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->failed_reason === 'Rate limit exceeded'))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->failed_at !== null))->toBeTrue();
});

test('sets poll_attempt and next_poll_at on messages when driver supports delivery status', function () {
    $batch = makeBulkSendableBatch('No variables here');
    insertBulkPendingMessages($batch, 2);

    Sms::extend('log', function () {
        return new class implements SmsGateway, SupportsBulkSms, SupportsDeliveryStatus
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                $ids = array_map(static fn ($i) => 'msg-'.$i, range(1, count($r->recipients)));

                return new BulkSmsResponse(successful: true, perRecipientIds: $ids);
            }

            public function fetchDeliveryStatus(string $id): DeliveryStatusResponse
            {
                return new DeliveryStatusResponse(outcome: DeliveryOutcome::Delivered);
            }
        };
    });

    $allIds = Message::where('message_batch_id', $batch->id)->pluck('id')->all();
    runBulkJob($batch, $allIds);

    $messages = Message::where('message_batch_id', $batch->id)->get();

    expect($messages->every(fn ($m) => $m->poll_attempt === 1))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->next_poll_at !== null))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Refund cases
// ---------------------------------------------------------------------------

test('refunds total message cost to user balance when bulk send fails', function () {
    $batch = makeBulkSendableBatch('No variables here');
    $user = User::find($batch->user_id);
    $balanceBefore = (float) $user->balance;

    insertBulkPendingMessages($batch, 3); // 3 × 0.30 = 0.90

    Sms::extend('log', function () {
        return new class implements SmsGateway, SupportsBulkSms
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: false);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                return new BulkSmsResponse(successful: false, errorMessage: 'Rate limit exceeded');
            }
        };
    });

    $allIds = Message::where('message_batch_id', $batch->id)->pluck('id')->all();
    runBulkJob($batch, $allIds);

    $user->refresh();
    $totalCost = round(3 * 0.30, 4);

    expect($user->balance)->toBe(round($balanceBefore + $totalCost, 4))
        ->and($batch->transactions()->where('direction', TransactionDirection::Credit)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Guard cases
// ---------------------------------------------------------------------------

test('does nothing when batch has no sender configured', function () {
    $user = User::factory()->create();
    $batch = Batch::factory()->for($user)->create(['sender_id' => null]);
    insertBulkPendingMessages($batch, 2);

    runBulkJob($batch);

    expect(Message::where('status', MessageStatus::Pending)->count())->toBe(2);
});

test('does nothing when driver does not implement SupportsBulkSms', function () {
    $batch = makeBulkSendableBatch('No variables here');
    insertBulkPendingMessages($batch, 2);

    Sms::extend('log', function () {
        return new class implements SmsGateway
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true);
            }
        };
    });

    $allIds = Message::where('message_batch_id', $batch->id)->pluck('id')->all();
    runBulkJob($batch, $allIds);

    // Driver does not support bulk — job exits early, messages stay pending.
    expect(Message::where('status', MessageStatus::Pending)->count())->toBe(2);
});

test('completes without error when batch has no pending messages', function () {
    $batch = makeBulkSendableBatch();

    runBulkJob($batch);

    expect(Message::count())->toBe(0);
});

test('only processes the specified message IDs', function () {
    $batch = makeBulkSendableBatch('No variables');
    insertBulkPendingMessages($batch, 3);

    $allIds = Message::where('message_batch_id', $batch->id)->orderBy('id')->pluck('id')->all();
    $targetIds = [$allIds[0], $allIds[1]]; // send first 2, leave third pending

    Sms::extend('log', function () {
        return new class implements SmsGateway, SupportsBulkSms
        {
            public function send(SmsRequest $r): SmsResponse
            {
                return new SmsResponse(successful: true);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                return new BulkSmsResponse(successful: true, gatewayBatchId: 'batch-abc');
            }
        };
    });

    runBulkJob($batch, $targetIds);

    expect(Message::where('status', MessageStatus::Sent)->count())->toBe(2)
        ->and(Message::where('status', MessageStatus::Pending)->count())->toBe(1)
        ->and(Message::where('id', $allIds[2])->value('status'))->toBe(MessageStatus::Pending);
});

test('SendBatchBulkSmsJob is dispatchable and carries the batch', function () {
    Queue::fake();

    $batch = makeBulkSendableBatch();

    SendBatchBulkSmsJob::dispatch($batch, [1, 2, 3])->onQueue('sms-sending');

    Queue::assertPushed(SendBatchBulkSmsJob::class, fn ($job) => $job->batch->id === $batch->id
        && $job->messageIds === [1, 2, 3]);
});
