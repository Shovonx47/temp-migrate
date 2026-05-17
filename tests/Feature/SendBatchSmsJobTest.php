<?php

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Enums\GatewayDriver;
use App\Enums\MessageStatus;
use App\Enums\SenderType;
use App\Jobs\SendBatchSmsJob;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\Sender;
use App\Models\User;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
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
 * The Gateway driver is always GatewayDriver::Log so the SmsManager can resolve it;
 * swap the resolved driver per test using Sms::extend('log', fn () => ...).
 */
function makeSendableBatch(
    string $body = 'Hello world',
    SenderType $senderType = SenderType::Numeric,
): MessageBatch {
    $user = User::factory()->create();

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Log,
        'is_enabled' => true,
        'config' => [],
    ]);

    $sender = Sender::factory()->create([
        'gateway_id' => $gateway->id,
        'type' => $senderType,
        'is_enabled' => true,
        'sender_id' => 'TestSender',
    ]);

    return MessageBatch::factory()
        ->for($user)
        ->create([
            'sender_id' => $sender->id,
            'body' => $body,
            'status' => 'completed',
        ]);
}

/** Insert pending Message rows directly, bypassing the billing pipeline. */
function insertPendingMessages(MessageBatch $batch, int $count = 3, string $body = 'Hello world'): void
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

/** Run the job synchronously. */
function runSendBatchJob(MessageBatch $batch): void
{
    (new SendBatchSmsJob($batch))->handle();
}

// ---------------------------------------------------------------------------
// Bulk strategy — no variables, driver implements SupportsBulkSms
// ---------------------------------------------------------------------------

test('bulk: marks all messages sent with per-recipient gateway IDs', function () {
    $batch = makeSendableBatch('No variables here');
    insertPendingMessages($batch, 3);

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

    runSendBatchJob($batch);

    $messages = Message::where('message_batch_id', $batch->id)->orderBy('id')->get();

    expect($messages)->toHaveCount(3)
        ->and($messages->every(fn ($m) => $m->status === MessageStatus::Sent))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->sent_at !== null))->toBeTrue()
        ->and($messages->pluck('gateway_message_id')->all())->toBe(['msg-1', 'msg-2', 'msg-3']);
});

test('bulk: marks all messages sent with shared batch ID when driver returns single ID', function () {
    $batch = makeSendableBatch('No variables here');
    insertPendingMessages($batch, 3);

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

    runSendBatchJob($batch);

    $messages = Message::where('message_batch_id', $batch->id)->get();

    expect($messages->every(fn ($m) => $m->status === MessageStatus::Sent))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->gateway_message_id === 'batch-xyz'))->toBeTrue();
});

test('bulk: marks all messages failed when gateway returns unsuccessful response', function () {
    $batch = makeSendableBatch('No variables here');
    insertPendingMessages($batch, 2);

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

    runSendBatchJob($batch);

    $messages = Message::where('message_batch_id', $batch->id)->get();

    expect($messages->every(fn ($m) => $m->status === MessageStatus::Failed))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->failed_reason === 'Rate limit exceeded'))->toBeTrue()
        ->and($messages->every(fn ($m) => $m->failed_at !== null))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Individual strategy — body has variables OR driver has no bulk support
// ---------------------------------------------------------------------------

test('individual: sends each message separately when body has variables', function () {
    $batch = makeSendableBatch('Hello {name}');
    insertPendingMessages($batch, 2, 'Hello John'); // resolved body, but batch has vars

    $calls = 0;

    Sms::extend('log', function () use (&$calls) {
        return new class($calls) implements SmsGateway, SupportsBulkSms
        {
            public function __construct(private int &$calls) {}

            public function send(SmsRequest $r): SmsResponse
            {
                $this->calls++;

                return new SmsResponse(successful: true, gatewayMessageId: 'ind-'.$this->calls);
            }

            public function sendBulk(BulkSmsRequest $r): BulkSmsResponse
            {
                // Should NOT be called.
                return new BulkSmsResponse(successful: true);
            }
        };
    });

    runSendBatchJob($batch);

    // Body has {name} so individual strategy must be used.
    expect($calls)->toBe(2);

    $messages = Message::where('message_batch_id', $batch->id)->orderBy('id')->get();

    expect($messages->every(fn ($m) => $m->status === MessageStatus::Sent))->toBeTrue()
        ->and($messages->pluck('gateway_message_id')->all())->toBe(['ind-1', 'ind-2']);
});

test('individual: falls back when driver does not implement SupportsBulkSms', function () {
    $batch = makeSendableBatch('No variables');
    insertPendingMessages($batch, 2);

    $calls = 0;

    // Driver implements only SmsGateway, NOT SupportsBulkSms.
    Sms::extend('log', function () use (&$calls) {
        return new class($calls) implements SmsGateway
        {
            public function __construct(private int &$calls) {}

            public function send(SmsRequest $r): SmsResponse
            {
                $this->calls++;

                return new SmsResponse(successful: true, gatewayMessageId: 'fb-'.$this->calls);
            }
        };
    });

    runSendBatchJob($batch);

    expect($calls)->toBe(2);

    $messages = Message::where('message_batch_id', $batch->id)->get();
    expect($messages->every(fn ($m) => $m->status === MessageStatus::Sent))->toBeTrue();
});

test('individual: marks message failed and continues when driver throws an exception', function () {
    $batch = makeSendableBatch('Hello {name}');
    insertPendingMessages($batch, 2, 'Hello John');

    $calls = 0;

    Sms::extend('log', function () use (&$calls) {
        return new class($calls) implements SmsGateway
        {
            public function __construct(private int &$calls) {}

            public function send(SmsRequest $r): SmsResponse
            {
                $this->calls++;

                if ($this->calls === 1) {
                    throw new RuntimeException('Network timeout');
                }

                return new SmsResponse(successful: true, gatewayMessageId: 'ok-2');
            }
        };
    });

    runSendBatchJob($batch);

    $messages = Message::where('message_batch_id', $batch->id)->orderBy('id')->get();

    expect($messages[0]->status)->toBe(MessageStatus::Failed)
        ->and($messages[0]->failed_reason)->toBe('Network timeout')
        ->and($messages[1]->status)->toBe(MessageStatus::Sent)
        ->and($messages[1]->gateway_message_id)->toBe('ok-2');
});

// ---------------------------------------------------------------------------
// Edge cases
// ---------------------------------------------------------------------------

test('does nothing when batch has no sender configured', function () {
    $user = User::factory()->create();
    $batch = MessageBatch::factory()->for($user)->create(['sender_id' => null]);
    insertPendingMessages($batch, 2);

    runSendBatchJob($batch);

    expect(Message::where('status', MessageStatus::Pending)->count())->toBe(2);
});

test('completes without error when batch has no pending messages', function () {
    $batch = makeSendableBatch();

    runSendBatchJob($batch);

    expect(Message::count())->toBe(0);
});

test('SendBatchSmsJob is dispatchable and carries the batch', function () {
    Queue::fake();

    $batch = makeSendableBatch();

    SendBatchSmsJob::dispatch($batch)->onQueue('sms-sending');

    Queue::assertPushed(SendBatchSmsJob::class, fn ($job) => $job->batch->id === $batch->id);
});
