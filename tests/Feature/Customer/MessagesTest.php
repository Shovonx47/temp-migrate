<?php

use App\Enums\BatchSource;
use App\Enums\BatchStatus;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\CreateQuickMessage;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\CreateSmartMessage;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ListMessages;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ViewMessage;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('customer'));
    seedDefaultPricing();
});

test('customer can list their messages', function () {
    $user = User::factory()->create();
    $messages = Message::factory()->for($user)->count(3)->create();

    actingAs($user, 'customer');

    Livewire::test(ListMessages::class, ['resource' => MessageResource::class])
        ->assertCanSeeTableRecords($messages);
});

test('customer can view a message', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->create();

    actingAs($user, 'customer');

    Livewire::test(ViewMessage::class, ['record' => $message->id, 'resource' => MessageResource::class])
        ->assertSee($message->uid);
});

test('customer cannot view messages of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $message = Message::factory()->for($other)->create();

    actingAs($user, 'customer');

    expect(fn () => Livewire::test(ViewMessage::class, ['record' => $message->id, 'resource' => MessageResource::class]))
        ->toThrow(ModelNotFoundException::class);
});

test('customer can send a quick message via TagsInput', function () {
    Bus::fake();

    $user = User::factory()->create(['balance' => 1000, 'sms_auto_approved' => true]);
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    actingAs($user, 'customer');

    Livewire::test(CreateQuickMessage::class, ['resource' => MessageResource::class])
        ->fillForm([
            'sender_id' => $sender->id,
            'phones' => ['01711000001', '01711000002'],
            'body' => 'Hello there',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::query()->where('user_id', $user->id)->firstOrFail();

    expect($batch->source_type)->toBe(BatchSource::Manual)
        ->and($batch->body)->toBe('Hello there')
        ->and($batch->sender_id)->toBe($sender->id)
        ->and($batch->status)->toBe(BatchStatus::Processing)
        ->and($batch->source_config['recipients'])->toEqual([
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]);

    Bus::assertDispatchedSync(
        ProcessBatchJob::class,
        fn (ProcessBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->sourceType === 'manual'
            && $job->body === 'Hello there'
            && $job->syncSending === true,
    );
});

test('quick message requires sender phones and body', function () {
    $user = User::factory()->create();

    actingAs($user, 'customer');

    Livewire::test(CreateQuickMessage::class, ['resource' => MessageResource::class])
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

test('customer can send a bulk message in one step with comma-separated phones', function () {
    Bus::fake();

    $user = User::factory()->create(['balance' => 1000, 'sms_auto_approved' => true]);
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    actingAs($user, 'customer');

    Livewire::test(CreateSmartMessage::class, ['resource' => MessageResource::class])
        ->fillForm([
            'sender_id' => $sender->id,
            'source' => 'manual',
            'phones' => ['01711000001', '01711000002, 01711000003'],
            'body' => 'Hello everyone',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::query()->where('user_id', $user->id)->firstOrFail();

    expect($batch->source_type)->toBe(BatchSource::Manual)
        ->and($batch->body)->toBe('Hello everyone')
        ->and($batch->sender_id)->toBe($sender->id)
        ->and($batch->status)->toBe(BatchStatus::Processing)
        ->and($batch->source_config['recipients'])->toHaveCount(3);

    Bus::assertDispatched(
        ProcessBatchJob::class,
        fn (ProcessBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->sourceType === 'manual',
    );
});

test('customer can send a bulk message in one step with manual recipients', function () {
    Bus::fake();

    $user = User::factory()->create(['balance' => 1000, 'sms_auto_approved' => true]);
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    actingAs($user, 'customer');

    Livewire::test(CreateSmartMessage::class, ['resource' => MessageResource::class])
        ->fillForm([
            'sender_id' => $sender->id,
            'source' => 'manual',
            'body' => 'Hello {name}, your bill is {amount}',
            'body_variables' => ['name', 'amount'],
            'recipients' => [
                [
                    'phone' => '01711000001',
                    'values' => ['name' => 'Alice', 'amount' => '500'],
                ],
                [
                    'phone' => '01711000002',
                    'values' => ['name' => 'Bob', 'amount' => '750'],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::query()->where('user_id', $user->id)->firstOrFail();

    expect($batch->source_type)->toBe(BatchSource::Manual)
        ->and($batch->body)->toBe('Hello {name}, your bill is {amount}')
        ->and($batch->sender_id)->toBe($sender->id)
        ->and($batch->status)->toBe(BatchStatus::Processing)
        ->and($batch->source_config['recipients'])->toHaveCount(2);

    Bus::assertDispatched(
        ProcessBatchJob::class,
        fn (ProcessBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->sourceType === 'manual'
            && $job->bodyVariables === ['name', 'amount'],
    );
});

test('customer can send a bulk message in one step with group recipients', function () {
    Bus::fake();

    $user = User::factory()->create(['balance' => 1000, 'sms_auto_approved' => true]);
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    $group = Group::factory()->for($user)->create();
    $contacts = Contact::factory()->for($user)->count(2)->create();
    $group->contacts()->attach($contacts);

    actingAs($user, 'customer');

    Livewire::test(CreateSmartMessage::class, ['resource' => MessageResource::class])
        ->fillForm([
            'sender_id' => $sender->id,
            'source' => 'group',
            'body' => 'Notice to all group members',
            'groups' => [(string) $group->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::query()->where('user_id', $user->id)->firstOrFail();

    expect($batch->source_type)->toBe(BatchSource::Group)
        ->and($batch->body)->toBe('Notice to all group members')
        ->and($batch->sender_id)->toBe($sender->id)
        ->and($batch->status)->toBe(BatchStatus::Processing)
        ->and($batch->source_config['group_ids'])->toEqual([(string) $group->id]);

    Bus::assertDispatched(
        ProcessBatchJob::class,
        fn (ProcessBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->sourceType === 'group',
    );
});

test('customer can submit message via create method with confirmation', function () {
    Bus::fake();

    $user = User::factory()->create(['balance' => 500, 'sms_auto_approved' => true]);
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    actingAs($user, 'customer');

    Livewire::test(CreateSmartMessage::class, ['resource' => MessageResource::class])
        ->fillForm([
            'sender_id' => $sender->id,
            'source' => 'manual',
            'phones' => ['01711000001, 01711000002'],
            'body' => 'Test message with confirmation modal',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::query()->where('user_id', $user->id)->firstOrFail();
    expect($batch->body)->toBe('Test message with confirmation modal');
});




