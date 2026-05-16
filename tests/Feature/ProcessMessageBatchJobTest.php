<?php

use App\Enums\MessageBatchStatus;
use App\Enums\MessageStatus;
use App\Jobs\ProcessMessageBatchJob;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\User;
use App\Services\RecipientFileParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function makeMessageBatch(?User $user = null): MessageBatch
{
    $user ??= User::factory()->create();

    return MessageBatch::factory()
        ->for($user)
        ->state(['sender_id' => null])
        ->create();
}

function runBatchJob(MessageBatch $batch, string $source, array $config, string $body = 'Hi', array $bodyVariables = []): void
{
    (new ProcessMessageBatchJob($batch, $source, $config, $body, $bodyVariables))
        ->handle(app(RecipientFileParser::class));
}

test('manual source creates messages for valid phones and reports invalid ones', function () {
    $batch = makeMessageBatch();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
            ['phone' => 'not-a-phone', 'values' => []],
            ['phone' => '', 'values' => []],
        ]],
        body: 'Hello there',
    );

    expect(Message::count())->toBe(2)
        ->and($batch->fresh()->status)->toBe(MessageBatchStatus::Completed)
        ->and($batch->fresh()->completed_at)->not->toBeNull();

    $reports = Storage::disk('local')->files('message-batches/'.$batch->id);
    expect($reports)->toHaveCount(1);

    $csv = Storage::disk('local')->get($reports[0]);
    expect($csv)->toContain('not-a-phone')
        ->and($csv)->toContain('Invalid phone number format')
        ->and($csv)->toContain('Phone number missing');
});

test('manual source completes cleanly when every recipient is valid', function () {
    $batch = makeMessageBatch();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]],
    );

    expect(Message::count())->toBe(2)
        ->and(Message::where('message_batch_id', $batch->id)->count())->toBe(2)
        ->and(Storage::disk('local')->files('message-batches/' . $batch->id))->toBeEmpty();
});

test('substitutes body variables per recipient', function () {
    $batch = makeMessageBatch();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => ['name' => 'Rahim', 'balance' => '500']],
            ['phone' => '01711000002', 'values' => ['name' => 'Karim', 'balance' => '250']],
        ]],
        body: 'Hi {name}, balance: {balance}',
        bodyVariables: ['name', 'balance'],
    );

    expect(Message::pluck('body')->all())->toBe([
        'Hi Rahim, balance: 500',
        'Hi Karim, balance: 250',
    ]);
});

test('upload source streams the file and applies mapping', function () {
    $batch = makeMessageBatch();

    $csv = "phone,first_name\n01711000001,Rahim\n01711000002,Karim\n";
    Storage::disk('local')->put('message-batches/'.$batch->id.'/source.csv', $csv);

    runBatchJob(
        $batch,
        'upload',
        [
            'disk' => 'local',
            'file_path' => 'message-batches/'.$batch->id.'/source.csv',
            'mapping' => [
                'phone' => 'phone',
                'variables' => ['name' => 'first_name'],
            ],
        ],
        body: 'Hi {name}',
        bodyVariables: ['name'],
    );

    expect(Message::count())->toBe(2)
        ->and(Message::pluck('body')->all())->toBe(['Hi Rahim', 'Hi Karim']);
});

test('group source pulls contacts via the relationship and skips blocked ones', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    $contacts = Contact::factory()->count(3)->for($user)->create();
    $blocked = Contact::factory()->blocked()->for($user)->create([
        'phone' => '+8801999999999',
    ]);
    $group->contacts()->sync([$contacts[0]->id, $contacts[1]->id, $contacts[2]->id, $blocked->id]);

    $batch = makeMessageBatch($user);

    runBatchJob(
        $batch,
        'group',
        ['group_ids' => [$group->id]],
        body: 'Hi',
    );

    expect(Message::count())->toBe(3)
        ->and(Message::where('status', MessageStatus::Pending)->count())->toBe(3);
});

test('writes a database notification for the user when the batch completes', function () {
    $user = User::factory()->create();
    $batch = makeMessageBatch($user);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => 'bad', 'values' => []],
        ]],
    );

    expect($user->notifications()->count())->toBe(1);
});

test('retry re-dispatches the job and resets batch state', function () {
    Illuminate\Support\Facades\Bus::fake();

    $batch = makeMessageBatch();
    $batch->forceFill([
        'status' => MessageBatchStatus::Failed,
        'failed_count' => 5,
        'failed_report_path' => 'some/old/report.csv',
        'completed_at' => now(),
        'body' => 'Hi {name}, your code is {code}',
        'source_type' => 'manual',
        'source_config' => ['recipients' => [['phone' => '01711000001', 'values' => ['name' => 'A', 'code' => '1']]]],
    ])->save();

    $batch->retry();

    $fresh = $batch->fresh();
    expect($fresh->status)->toBe(MessageBatchStatus::Processing)
        ->and($fresh->failed_count)->toBe(0)
        ->and($fresh->failed_report_path)->toBeNull()
        ->and($fresh->completed_at)->toBeNull();

    Illuminate\Support\Facades\Bus::assertDispatched(
        ProcessMessageBatchJob::class,
        fn (ProcessMessageBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->bodyVariables === ['name', 'code']
    );
});

test('missing upload file is treated as zero recipients, not a fatal error', function () {
    $batch = makeMessageBatch();

    runBatchJob(
        $batch,
        'upload',
        [
            'disk' => 'local',
            'file_path' => 'message-batches/'.$batch->id.'/missing.csv',
            'mapping' => ['phone' => 'phone', 'variables' => []],
        ],
    );

    expect($batch->fresh()->status)->toBe(MessageBatchStatus::Completed)
        ->and(Message::count())->toBe(0);
});
