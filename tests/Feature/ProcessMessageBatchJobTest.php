<?php

use App\Enums\MessageBatchStatus;
use App\Enums\MessageStatus;
use App\Enums\SenderType;
use App\Enums\TransactionDirection;
use App\Jobs\ProcessMessageBatchJob;
use App\Jobs\SendBatchSmsJob;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\Operator;
use App\Models\Sender;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RecipientFileParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    seedDefaultPricing();
    // Prevent SendBatchSmsJob from executing synchronously during these tests.
    // ProcessMessageBatchJob is tested via handle() directly; the downstream
    // sending job is not part of its contract and requires a configured gateway.
    // Only this one job is faked — notifications and other dispatches run normally.
    Bus::fake([SendBatchSmsJob::class]);
});

function makeMessageBatch(?User $user = null, SenderType $senderType = SenderType::Numeric): MessageBatch
{
    $user ??= User::factory()->create();
    $sender = Sender::factory()->state(['type' => $senderType])->create();

    return MessageBatch::factory()
        ->for($user)
        ->state(['sender_id' => $sender->id])
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
        ->and(Storage::disk('local')->files('message-batches/'.$batch->id))->toBeEmpty();
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

test('fails recipients with missing variable values and reports them', function () {
    $batch = makeMessageBatch();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => ['name' => 'Rahim', 'order_id' => 'A100']],
            ['phone' => '01711000002', 'values' => ['name' => 'Karim', 'order_id' => '']],
            ['phone' => '01711000003', 'values' => ['name' => '   ', 'order_id' => 'A102']],
        ]],
        body: 'Hi {name}, your #{order_id} confirmed',
        bodyVariables: ['name', 'order_id'],
    );

    expect(Message::count())->toBe(1)
        ->and(Message::first()->body)->toBe('Hi Rahim, your #A100 confirmed')
        ->and($batch->fresh()->invalid_count)->toBe(2)
        ->and($batch->fresh()->valid_count)->toBe(1)
        ->and($batch->fresh()->total_recipients)->toBe(3);

    $reports = Storage::disk('local')->files('message-batches/'.$batch->id);
    $csv = Storage::disk('local')->get($reports[0]);
    expect($csv)->toContain('Missing value for: order_id')
        ->and($csv)->toContain('Missing value for: name');
});

test('group source ignores body variables and does not fail on missing values', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->create();
    $group->contacts()->sync([$contact->id]);

    $batch = makeMessageBatch($user);

    runBatchJob(
        $batch,
        'group',
        ['group_ids' => [$group->id]],
        body: 'Hi {name}',
        bodyVariables: ['name'],
    );

    expect(Message::count())->toBe(1)
        ->and(Message::first()->body)->toBe('Hi {name}')
        ->and($batch->fresh()->invalid_count)->toBe(0)
        ->and($batch->fresh()->valid_count)->toBe(1)
        ->and($batch->fresh()->total_recipients)->toBe(1);
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
        ->and(Message::pluck('body')->all())->toBe(['Hi Rahim', 'Hi Karim'])
        ->and($batch->fresh()->total_recipients)->toBe(2)
        ->and($batch->fresh()->valid_count)->toBe(2)
        ->and($batch->fresh()->invalid_count)->toBe(0);
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
        ->and(Message::where('status', MessageStatus::Pending)->count())->toBe(3)
        ->and($batch->fresh()->total_recipients)->toBe(3)
        ->and($batch->fresh()->valid_count)->toBe(3)
        ->and($batch->fresh()->invalid_count)->toBe(0);
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

test('retry re-dispatches the job and preserves progress for resume', function () {
    Bus::fake();

    $batch = makeMessageBatch();
    $batch->forceFill([
        'status' => MessageBatchStatus::Failed,
        'total_recipients' => 5,
        'valid_count' => 3,
        'invalid_count' => 2,
        'last_processed_cursor' => 4,
        'failed_report_path' => 'some/old/report.csv',
        'completed_at' => now(),
        'body' => 'Hi {name}, your code is {code}',
        'source_type' => 'manual',
        'source_config' => ['recipients' => [['phone' => '01711000001', 'values' => ['name' => 'A', 'code' => '1']]]],
    ])->save();

    $batch->retry();

    $fresh = $batch->fresh();
    expect($fresh->status)->toBe(MessageBatchStatus::Processing)
        ->and($fresh->valid_count)->toBe(3)
        ->and($fresh->invalid_count)->toBe(2)
        ->and($fresh->last_processed_cursor)->toBe(4)
        ->and($fresh->failed_report_path)->toBe('some/old/report.csv')
        ->and($fresh->completed_at)->toBeNull();

    Bus::assertDispatched(
        ProcessMessageBatchJob::class,
        fn (ProcessMessageBatchJob $job): bool => $job->batch->id === $batch->id
            && $job->bodyVariables === ['name', 'code']
    );
});

test('retry refuses to dispatch when batch is not in Failed status', function () {
    Bus::fake();

    $batch = makeMessageBatch();
    $batch->forceFill([
        'status' => MessageBatchStatus::Processing,
        'body' => 'Hi',
        'source_type' => 'manual',
        'source_config' => ['recipients' => []],
    ])->save();

    $batch->retry();

    Bus::assertNotDispatched(ProcessMessageBatchJob::class);
});

test('manual source resumes from cursor and skips already-processed entries', function () {
    $batch = makeMessageBatch();
    $batch->forceFill([
        'status' => MessageBatchStatus::Processing,
        'valid_count' => 2,
        'invalid_count' => 0,
        'last_processed_cursor' => 1, // already processed indexes 0 and 1
        'failed_report_path' => null,
    ])->save();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []], // already done
            ['phone' => '01711000002', 'values' => []], // already done (cursor stops here)
            ['phone' => '01711000003', 'values' => []], // should be processed
            ['phone' => '01711000004', 'values' => []], // should be processed
        ]],
    );

    expect(Message::count())->toBe(2)
        ->and(Message::pluck('to')->map(fn ($p): string => (string) $p)->all())->toBe(['+8801711000003', '+8801711000004'])
        ->and($batch->fresh()->valid_count)->toBe(4)
        ->and($batch->fresh()->invalid_count)->toBe(0)
        ->and($batch->fresh()->last_processed_cursor)->toBe(3)
        ->and($batch->fresh()->status)->toBe(MessageBatchStatus::Completed);
});

test('upload source resumes from cursor based on csv row index', function () {
    $batch = makeMessageBatch();

    $csv = "phone\n01711000001\n01711000002\n01711000003\n01711000004\n";
    Storage::disk('local')->put('message-batches/'.$batch->id.'/recipients.csv', $csv);

    $batch->forceFill([
        'status' => MessageBatchStatus::Processing,
        'valid_count' => 2,
        'invalid_count' => 0,
        'last_processed_cursor' => 1, // already processed rows 0 and 1
    ])->save();

    runBatchJob(
        $batch,
        'upload',
        [
            'disk' => 'local',
            'file_path' => 'message-batches/'.$batch->id.'/recipients.csv',
            'mapping' => ['phone' => 'phone', 'variables' => []],
        ],
    );

    expect(Message::count())->toBe(2)
        ->and(Message::pluck('to')->map(fn ($p): string => (string) $p)->all())->toBe(['+8801711000003', '+8801711000004'])
        ->and($batch->fresh()->last_processed_cursor)->toBe(3);
});

test('group source resumes from cursor based on contact id', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    $contacts = collect(range(1, 4))->map(
        fn (int $i): Contact => Contact::factory()->for($user)->create([
            'phone' => "+880171000000{$i}",
        ])
    );
    $contacts->each(fn (Contact $c) => $c->groups()->attach($group));

    $batch = makeMessageBatch($user);
    $batch->forceFill([
        'status' => MessageBatchStatus::Processing,
        'valid_count' => 2,
        'last_processed_cursor' => $contacts[1]->id, // already processed first two
    ])->save();

    runBatchJob(
        $batch,
        'group',
        ['group_ids' => [$group->id]],
    );

    expect(Message::count())->toBe(2)
        ->and(Message::pluck('to')->map(fn ($p): string => (string) $p)->all())->toBe([
            (string) $contacts[2]->phone,
            (string) $contacts[3]->phone,
        ])
        ->and($batch->fresh()->last_processed_cursor)->toBe($contacts[3]->id);
});

test('first run advances cursor and a subsequent retry resumes from where it stopped', function () {
    $batch = makeMessageBatch();

    // First run: complete batch end-to-end.
    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]],
    );

    $afterFirst = $batch->fresh();
    expect(Message::count())->toBe(2)
        ->and($afterFirst->last_processed_cursor)->toBe(1)
        ->and($afterFirst->valid_count)->toBe(2);

    // Simulate the batch having failed mid-run after processing 2 items, then resume with more recipients.
    $batch->refresh();
    $batch->update(['status' => MessageBatchStatus::Processing]);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []], // already processed
            ['phone' => '01711000002', 'values' => []], // already processed
            ['phone' => '01711000003', 'values' => []], // new
        ]],
    );

    expect(Message::count())->toBe(3)
        ->and($batch->fresh()->valid_count)->toBe(3)
        ->and($batch->fresh()->last_processed_cursor)->toBe(2);
});

test('failed report is appended to on retry rather than recreated', function () {
    $batch = makeMessageBatch();

    // First run produces one invalid row.
    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => 'bad-1', 'values' => []],
        ]],
    );

    $firstPath = $batch->fresh()->failed_report_path;
    expect($firstPath)->not->toBeNull();

    // Simulate retry: keep the existing cursor and failed_report_path; flip status back to Processing.
    $batch->refresh();
    $batch->update(['status' => MessageBatchStatus::Processing]);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => 'bad-1', 'values' => []], // already processed (cursor 0)
            ['phone' => 'bad-2', 'values' => []], // new invalid
        ]],
    );

    expect($batch->fresh()->failed_report_path)->toBe($firstPath);

    $content = Storage::disk('local')->get($firstPath);
    // Header appears once, both invalid rows present.
    expect(substr_count($content, 'Phone,Reason'))->toBe(1)
        ->and($content)->toContain('bad-1')
        ->and($content)->toContain('bad-2');
});

test('concurrent retry guard: job aborts if batch status is not Processing', function () {
    $batch = makeMessageBatch();
    $batch->forceFill(['status' => MessageBatchStatus::Failed])->save();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]],
    );

    // Job should have exited early without inserting anything or changing status.
    expect(Message::count())->toBe(0)
        ->and($batch->fresh()->status)->toBe(MessageBatchStatus::Failed);
});

test('inserted messages carry parts, rate, and cost resolved per operator', function () {
    // seedDefaultPricing in beforeEach sets non_masking 0.3 for all operators
    $user = User::factory()->create(['balance' => 100]);
    $batch = makeMessageBatch($user, SenderType::Numeric);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []], // GP
            ['phone' => '01711000002', 'values' => []], // GP
        ]],
        body: 'Hi',
    );

    $messages = Message::orderBy('id')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages->pluck('parts')->all())->toBe([1, 1])
        ->and($messages->pluck('rate')->map(fn ($r): float => (float) $r)->all())->toBe([0.3, 0.3])
        ->and($messages->pluck('cost')->map(fn ($c): float => (float) $c)->all())->toBe([0.3, 0.3]);
});

test('user balance is debited by the sum of message costs', function () {
    $user = User::factory()->create(['balance' => 100]);
    $batch = makeMessageBatch($user, SenderType::Numeric);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
            ['phone' => '01711000003', 'values' => []],
        ]],
        body: 'Hi',
    );

    // 3 messages × 0.3 non_masking_price = 0.9 debit
    expect((float) $user->fresh()->balance)->toBe(99.1);
});

test('one debit Transaction is created per inserted message with running balance', function () {
    $user = User::factory()->create(['balance' => 50]);
    $batch = makeMessageBatch($user, SenderType::Numeric);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]],
        body: 'Hi',
    );

    $txns = Transaction::where('user_id', $user->id)
        ->orderBy('id')
        ->get();

    expect($txns)->toHaveCount(2)
        ->and($txns->pluck('direction')->all())->toBe([TransactionDirection::Debit, TransactionDirection::Debit])
        ->and($txns->pluck('amount')->map(fn ($a): float => (float) $a)->all())->toBe([0.3, 0.3])
        ->and((float) $txns[0]->balance_before)->toBe(50.0)
        ->and((float) $txns[0]->balance_after)->toBe(49.7)
        ->and((float) $txns[1]->balance_before)->toBe(49.7)
        ->and((float) $txns[1]->balance_after)->toBe(49.4);

    expect($txns[0]->transactionable_id)->toBe(Message::orderBy('id')->first()->id);
});

test('multi-part message charges rate times parts', function () {
    $user = User::factory()->create(['balance' => 100]);
    $batch = makeMessageBatch($user, SenderType::Numeric);

    $longBody = str_repeat('a', 200); // 200 chars -> 2 parts in GSM-7

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]],
        body: $longBody,
    );

    $message = Message::first();

    expect($message->parts)->toBe(2)
        ->and((float) $message->cost)->toBe(0.6)             // 0.3 × 2
        ->and((float) $user->fresh()->balance)->toBe(99.4);  // 100 - 0.6
});

test('masking sender uses the masking_price, non-masking uses non_masking_price', function () {
    $user = User::factory()->create(['balance' => 100]);

    $maskingBatch = makeMessageBatch($user, SenderType::AlphaNumeric);
    runBatchJob(
        $maskingBatch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]],
        body: 'Hi',
    );

    expect((float) Message::orderBy('id')->first()->rate)->toBe(0.5);

    $nonMaskingBatch = makeMessageBatch($user, SenderType::Numeric);
    runBatchJob(
        $nonMaskingBatch,
        'manual',
        ['recipients' => [['phone' => '01811000001', 'values' => []]]], // Robi (18)
        body: 'Hi',
    );

    expect((float) Message::orderBy('id', 'desc')->first()->rate)->toBe(0.3);
});

test('user-specific operator rate override beats the operator default', function () {
    $user = User::factory()->create(['balance' => 100]);
    $gp = Operator::where('name', 'Grameenphone')->first();
    $user->operatorRates()->create([
        'operator_id' => $gp->id,
        'masking_price' => null,
        'non_masking_price' => 0.10, // override only non-masking
    ]);

    $batch = makeMessageBatch($user, SenderType::Numeric);
    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]], // GP
        body: 'Hi',
    );

    expect((float) Message::first()->rate)->toBe(0.10);
});

test('operator without rate set causes the batch to Fail with a notification', function () {
    seedDefaultPricing(masking: 0.5, nonMasking: 0.3);
    // Wipe one operator's non_masking_price so the batch hits the strict failure
    Operator::where('name', 'Grameenphone')->update(['non_masking_price' => null]);

    $user = User::factory()->create(['balance' => 100]);
    $batch = makeMessageBatch($user, SenderType::Numeric);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]], // GP -> no rate
        body: 'Hi',
    );

    expect($batch->fresh()->status)->toBe(MessageBatchStatus::Failed)
        ->and(Message::count())->toBe(0)
        ->and($user->fresh()->notifications()->first()->data['title'] ?? null)
        ->toContain('pricing not configured');
});

test('batch without a sender Fails with a sender-required notification', function () {
    $user = User::factory()->create(['balance' => 100]);

    $batch = MessageBatch::factory()
        ->for($user)
        ->state(['sender_id' => null])
        ->create();

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]],
        body: 'Hi',
    );

    expect($batch->fresh()->status)->toBe(MessageBatchStatus::Failed)
        ->and($user->fresh()->notifications()->first()->data['title'] ?? null)
        ->toContain('sender missing');
});

test('insufficient balance halts batch with Failed status and inserts nothing in that chunk', function () {
    $user = User::factory()->create(['balance' => 0.2]); // < 0.3 per message
    $batch = makeMessageBatch($user, SenderType::Numeric);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]],
        body: 'Hi',
    );

    expect(Message::count())->toBe(0)
        ->and(Transaction::count())->toBe(0)
        ->and((float) $user->fresh()->balance)->toBe(0.2)
        ->and($batch->fresh()->status)->toBe(MessageBatchStatus::Failed);
});

test('insufficient balance notifies the user with a top-up message', function () {
    $user = User::factory()->create(['balance' => 0.1]);
    $batch = makeMessageBatch($user, SenderType::Numeric);

    runBatchJob(
        $batch,
        'manual',
        ['recipients' => [['phone' => '01711000001', 'values' => []]]],
        body: 'Hi',
    );

    $notification = $user->fresh()->notifications()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->data['title'] ?? null)->toContain('insufficient balance');
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
