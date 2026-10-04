<?php

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\SenderType;
use App\Enums\TransactionDirection;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Message;
use App\Models\Sender;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedDefaultPricing();
});

/**
 * @param  array<int, string>  $phones
 */
function makeCampaignWithRecipients(
    User $user,
    array $phones,
    SenderType $senderType = SenderType::Numeric,
    CampaignStatus $status = CampaignStatus::Processing,
): Campaign {
    $sender = Sender::factory()->state(['type' => $senderType])->create();
    $group = Group::factory()->for($user)->create();

    $contacts = collect($phones)->map(
        fn (string $phone): Contact => Contact::factory()->for($user)->create(['phone' => $phone])
    );
    $contacts->each(fn (Contact $c) => $c->groups()->attach($group));

    $campaign = Campaign::factory()
        ->for($user)
        ->state([
            'sender_id' => $sender->id,
            'group_id' => $group->id,
            'status' => $status,
            'body' => 'Hi',
            'total_recipients' => $contacts->count(),
        ])
        ->create();

    $now = now();
    $rows = $contacts->map(fn (Contact $c): array => [
        'campaign_id' => $campaign->id,
        'contact_id' => $c->id,
        'phone' => (string) $c->phone,
        'status' => CampaignRecipientStatus::Pending->value,
        'created_at' => $now,
        'updated_at' => $now,
    ])->all();

    DB::table('campaign_contact')->insert($rows);

    return $campaign->fresh();
}

test('processes pending recipients and marks campaign Completed', function () {
    $user = User::factory()->create(['balance' => 100]);

    $campaign = makeCampaignWithRecipients($user, [
        '+8801711000001',
        '+8801711000002',
    ]);

    (new ProcessCampaignJob($campaign))->handle();

    $fresh = $campaign->fresh();
    expect($fresh->status)->toBe(CampaignStatus::Completed)
        ->and($fresh->completed_at)->not->toBeNull()
        ->and($fresh->started_at)->not->toBeNull()
        ->and((int) $fresh->total_sent)->toBe(2)
        ->and(Message::where('campaign_id', $campaign->id)->count())->toBe(2)
        ->and(DB::table('campaign_contact')->where('campaign_id', $campaign->id)->where('status', 'sent')->count())->toBe(2);
});

test('debits user balance and writes one Transaction per recipient', function () {
    $user = User::factory()->create(['balance' => 50]);
    $campaign = makeCampaignWithRecipients($user, [
        '+8801711000001',
        '+8801711000002',
    ]);

    (new ProcessCampaignJob($campaign))->handle();

    expect((float) $user->fresh()->balance)->toBe(49.4); // 50 - (0.3 * 2)

    $txns = Transaction::where('user_id', $user->id)->orderBy('id')->get();

    expect($txns)->toHaveCount(2)
        ->and($txns->pluck('direction')->all())->toBe([TransactionDirection::Debit, TransactionDirection::Debit])
        ->and($txns->pluck('amount')->map(fn ($a): float => (float) $a)->all())->toBe([0.3, 0.3])
        ->and((float) $txns[0]->balance_before)->toBe(50.0)
        ->and((float) $txns[0]->balance_after)->toBe(49.7)
        ->and((float) $txns[1]->balance_before)->toBe(49.7)
        ->and((float) $txns[1]->balance_after)->toBe(49.4);
});

test('campaign_contact pivot rows record message_id, cost, and sent_at', function () {
    $user = User::factory()->create(['balance' => 100]);
    $campaign = makeCampaignWithRecipients($user, ['+8801711000001']);

    (new ProcessCampaignJob($campaign))->handle();

    $row = DB::table('campaign_contact')->where('campaign_id', $campaign->id)->first();

    expect($row->status)->toBe('sent')
        ->and((float) $row->cost)->toBe(0.3)
        ->and($row->message_id)->not->toBeNull()
        ->and($row->sent_at)->not->toBeNull();
});

test('does nothing when campaign is not in Processing status (concurrent guard)', function () {
    $user = User::factory()->create(['balance' => 100]);
    $campaign = makeCampaignWithRecipients($user, ['+8801711000001'], status: CampaignStatus::Scheduled);

    (new ProcessCampaignJob($campaign))->handle();

    expect(Message::count())->toBe(0)
        ->and($campaign->fresh()->status)->toBe(CampaignStatus::Scheduled);
});

test('insufficient balance fails the campaign without sending any message', function () {
    $user = User::factory()->create(['balance' => 0.1]); // < 0.3
    $campaign = makeCampaignWithRecipients($user, [
        '+8801711000001',
        '+8801711000002',
    ]);

    (new ProcessCampaignJob($campaign))->handle();

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Failed)
        ->and(Message::count())->toBe(0)
        ->and(Transaction::count())->toBe(0)
        ->and((float) $user->fresh()->balance)->toBe(0.1)
        ->and(DB::table('campaign_contact')->where('status', 'pending')->count())->toBe(2);
});

test('campaign without a sender fails with a notification', function () {
    $user = User::factory()->create(['balance' => 100]);

    $group = Group::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->create(['phone' => '+8801711000001']);
    $contact->groups()->attach($group);

    $campaign = Campaign::factory()
        ->for($user)
        ->state([
            'sender_id' => null,
            'group_id' => $group->id,
            'status' => CampaignStatus::Processing,
            'body' => 'Hi',
            'total_recipients' => 1,
        ])
        ->create();

    DB::table('campaign_contact')->insert([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'phone' => (string) $contact->phone,
        'status' => CampaignRecipientStatus::Pending->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (new ProcessCampaignJob($campaign))->handle();

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Failed)
        ->and($user->fresh()->notifications()->first()->data['title'] ?? null)
        ->toContain('sender missing');
});

test('masking sender charges the masking rate', function () {
    $user = User::factory()->create(['balance' => 100]);
    $campaign = makeCampaignWithRecipients(
        $user,
        ['+8801711000001'],
        senderType: SenderType::AlphaNumeric,
    );

    (new ProcessCampaignJob($campaign))->handle();

    expect((float) Message::first()->rate)->toBe(0.5)
        ->and((float) Message::first()->cost)->toBe(0.5);
});

test('processes only pending rows on subsequent runs (resume contract)', function () {
    $user = User::factory()->create(['balance' => 100]);
    $campaign = makeCampaignWithRecipients($user, [
        '+8801711000001',
        '+8801711000002',
    ]);

    // Mark one row as already sent.
    DB::table('campaign_contact')
        ->where('campaign_id', $campaign->id)
        ->orderBy('id')
        ->limit(1)
        ->update([
            'status' => CampaignRecipientStatus::Sent->value,
            'sent_at' => now(),
        ]);

    (new ProcessCampaignJob($campaign))->handle();

    // Only the pending one became a Message.
    expect(Message::where('campaign_id', $campaign->id)->count())->toBe(1)
        ->and((int) $campaign->fresh()->total_sent)->toBe(1);
});
