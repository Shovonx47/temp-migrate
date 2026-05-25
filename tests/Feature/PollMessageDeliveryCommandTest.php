<?php

use App\Enums\GatewayDriver;
use App\Enums\MessageStatus;
use App\Jobs\PollMessageDeliveryJob;
use App\Models\Batch;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSentMessageWithPollColumns(array $overrides = [], ?User $user = null): Message
{
    $user ??= User::factory()->create();

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Log,
        'is_enabled' => true,
        'credentials' => [],
    ]);

    $sender = Sender::factory()->create(['gateway_id' => $gateway->id, 'is_enabled' => true]);

    $batch = Batch::factory()->for($user)->create(['sender_id' => $sender->id]);

    return Message::factory()
        ->for($user)
        ->sent()
        ->state(array_merge([
            'campaign_id' => null,
            'sender_id' => $sender->id,
            'message_batch_id' => $batch->id,
            'gateway_message_id' => 'gw-001',
            'sent_at' => now()->subMinutes(3),
            'poll_attempt' => 1,
            'next_poll_at' => now()->subMinute(), // already due
        ], $overrides))
        ->create();
}

// ---------------------------------------------------------------------------
// Dispatch
// ---------------------------------------------------------------------------

test('dispatches PollMessageDeliveryJob for messages that are due', function () {
    Queue::fake();

    $message = makeSentMessageWithPollColumns();

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertPushed(PollMessageDeliveryJob::class, fn ($job) => $job->messageId === $message->id);
});

test('does not dispatch for messages whose next_poll_at is in the future', function () {
    Queue::fake();

    makeSentMessageWithPollColumns(['next_poll_at' => now()->addMinutes(5)]);

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('does not dispatch for messages without a gateway_message_id', function () {
    Queue::fake();

    makeSentMessageWithPollColumns(['gateway_message_id' => null, 'next_poll_at' => now()->subMinute()]);

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('does not dispatch for messages not in Sent status', function () {
    Queue::fake();

    makeSentMessageWithPollColumns(['status' => MessageStatus::Delivered, 'next_poll_at' => now()->subMinute()]);

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('does not dispatch for messages sent more than 48 hours ago', function () {
    Queue::fake();

    makeSentMessageWithPollColumns(['sent_at' => now()->subHours(49), 'next_poll_at' => now()->subMinute()]);

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('does not dispatch for messages with no next_poll_at', function () {
    Queue::fake();

    makeSentMessageWithPollColumns(['next_poll_at' => null, 'poll_attempt' => null]);

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertNothingPushed();
});

// ---------------------------------------------------------------------------
// Chunk limit
// ---------------------------------------------------------------------------

test('respects the chunk limit and dispatches only up to the specified count', function () {
    Queue::fake();

    // Share one user across all messages to avoid phone uniqueness collisions.
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        makeSentMessageWithPollColumns([
            'gateway_message_id' => "gw-{$i}",
            'next_poll_at' => now()->subMinutes($i + 1),
        ], $user);
    }

    $this->artisan('messages:poll-delivery --chunk=3')->assertSuccessful();

    Queue::assertPushed(PollMessageDeliveryJob::class, 3);
});

// ---------------------------------------------------------------------------
// Recovery after Redis flush
// ---------------------------------------------------------------------------

test('recovers stuck Sent messages after a Redis flush by dispatching on the next tick', function () {
    Queue::fake();

    // Share one user to avoid phone uniqueness collisions.
    $user = User::factory()->create();

    // Simulate messages that were scheduled but the Redis jobs were lost.
    // They have poll_attempt and next_poll_at set in DB, which is all we need.
    $message1 = makeSentMessageWithPollColumns(['gateway_message_id' => 'gw-a', 'next_poll_at' => now()->subMinutes(10)], $user);
    $message2 = makeSentMessageWithPollColumns(['gateway_message_id' => 'gw-b', 'next_poll_at' => now()->subMinutes(5)], $user);

    $this->artisan('messages:poll-delivery')->assertSuccessful();

    Queue::assertPushed(PollMessageDeliveryJob::class, 2);
    Queue::assertPushed(PollMessageDeliveryJob::class, fn ($job) => $job->messageId === $message1->id);
    Queue::assertPushed(PollMessageDeliveryJob::class, fn ($job) => $job->messageId === $message2->id);
});
