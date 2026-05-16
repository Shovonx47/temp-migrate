<?php

use App\Enums\MessageBatchStatus;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\CreateQuickMessage;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ListMessages;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ViewMessage;
use App\Jobs\ProcessMessageBatchJob;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their messages', function () {
    $user = User::factory()->create();
    $messages = Message::factory()->for($user)->count(3)->create();

    livewire(ListMessages::class, ['resource' => MessageResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($messages);
});

test('customer can view a message', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->create();

    livewire(ViewMessage::class, ['record' => $message->id, 'resource' => MessageResource::class])
        ->actingAs($user)
        ->assertSee($message->uid);
});

test('customer cannot view messages of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $message = Message::factory()->for($other)->create();

    livewire(ViewMessage::class, ['record' => $message->id, 'resource' => MessageResource::class])
        ->actingAs($user)
        ->assertForbidden();
});

test('customer can send a quick message via TagsInput', function () {
    Bus::fake();

    $user = User::factory()->create(['balance' => 1000]);
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    livewire(CreateQuickMessage::class, ['resource' => MessageResource::class])
        ->actingAs($user)
        ->fillForm([
            'sender_id' => $sender->id,
            'phones' => ['01711000001', '01711000002'],
            'body' => 'Hello there',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = MessageBatch::query()->where('user_id', $user->id)->firstOrFail();

    expect($batch->source_type)->toBe('manual')
        ->and($batch->body)->toBe('Hello there')
        ->and($batch->sender_id)->toBe($sender->id)
        ->and($batch->status)->toBe(MessageBatchStatus::Processing)
        ->and($batch->source_config['recipients'])->toEqual([
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]);

    Bus::assertDispatched(
        ProcessMessageBatchJob::class,
        fn (ProcessMessageBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->sourceType === 'manual'
            && $job->body === 'Hello there',
    );
});

test('quick message requires sender phones and body', function () {
    $user = User::factory()->create();

    livewire(CreateQuickMessage::class, ['resource' => MessageResource::class])
        ->actingAs($user)
        ->fillForm([
            'sender_id' => null,
            'phones' => [],
            'body' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'sender_id' => 'required',
            'phones' => 'required',
            'body' => 'required',
        ]);
});
