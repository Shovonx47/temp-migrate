<?php

use App\Enums\BatchStatus;
use App\Enums\MessageStatus;
use App\Models\Batch;
use App\Models\Message;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('purges messages stuck in pending or sending for more than 8 minutes', function () {
    $staleTime = Carbon::now()->subMinutes(10);

    $staleMessage = Message::factory()->create([
        'status' => MessageStatus::Pending,
        'updated_at' => $staleTime,
    ]);

    $freshMessage = Message::factory()->create([
        'status' => MessageStatus::Pending,
        'updated_at' => Carbon::now()->subMinutes(2),
    ]);

    $this->artisan('queue:purge-stale --minutes=8')
        ->assertExitCode(0);

    expect(Message::withoutGlobalScopes()->find($staleMessage->id)->status)
        ->toBe(MessageStatus::Failed);

    expect(Message::withoutGlobalScopes()->find($freshMessage->id)->status)
        ->toBe(MessageStatus::Pending);
});

it('purges batches stuck in processing for more than 8 minutes', function () {
    $staleBatch = Batch::factory()->create([
        'status' => BatchStatus::Processing,
        'updated_at' => Carbon::now()->subMinutes(15),
    ]);

    $freshBatch = Batch::factory()->create([
        'status' => BatchStatus::Processing,
        'updated_at' => Carbon::now()->subMinutes(3),
    ]);

    $this->artisan('queue:purge-stale --minutes=8')
        ->assertExitCode(0);

    expect(Batch::withoutGlobalScopes()->find($staleBatch->id)->status)
        ->toBe(BatchStatus::Failed);

    expect(Batch::withoutGlobalScopes()->find($freshBatch->id)->status)
        ->toBe(BatchStatus::Processing);
});

it('does nothing when the queue is clean', function () {
    $this->artisan('queue:purge-stale --minutes=8')
        ->expectsOutput('✓ Queue is clean — no stale items found.')
        ->assertExitCode(0);
});

it('previews what would be purged in dry-run mode without making changes', function () {
    Message::factory()->create([
        'status' => MessageStatus::Sending,
        'updated_at' => Carbon::now()->subMinutes(20),
    ]);

    $this->artisan('queue:purge-stale --minutes=8 --dry-run')
        ->assertExitCode(0);

    // Nothing should have changed in dry-run mode
    expect(Message::withoutGlobalScopes()->where('status', MessageStatus::Sending)->count())
        ->toBe(1);
});
