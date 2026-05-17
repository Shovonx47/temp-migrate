<?php

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Support\CampaignDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('snapshot copies valid, non-blocked group contacts into the pivot once', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    $contacts = collect(['+8801711000001', '+8801711000002', '+8801711000003'])->map(
        fn (string $phone) => Contact::factory()->for($user)->create(['phone' => $phone]),
    );
    $blocked = Contact::factory()->blocked()->for($user)->create(['phone' => '+8801711999999']);

    collect([...$contacts, $blocked])->each(fn (Contact $c) => $c->groups()->attach($group));

    $campaign = Campaign::factory()->for($user)->state([
        'group_id' => $group->id,
        'status' => CampaignStatus::Draft,
    ])->create();

    $count = app(CampaignDispatcher::class)->snapshotRecipients($campaign);

    expect($count)->toBe(3)
        ->and($campaign->fresh()->total_recipients)->toBe(3)
        ->and(DB::table('campaign_contact')->where('campaign_id', $campaign->id)->count())->toBe(3);
});

test('snapshot is idempotent — running twice does not double-insert', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    $contact = Contact::factory()->for($user)->create(['phone' => '+8801711000001']);
    $contact->groups()->attach($group);

    $campaign = Campaign::factory()->for($user)->state([
        'group_id' => $group->id,
    ])->create();

    $dispatcher = app(CampaignDispatcher::class);
    $dispatcher->snapshotRecipients($campaign);
    $dispatcher->snapshotRecipients($campaign);

    expect(DB::table('campaign_contact')->where('campaign_id', $campaign->id)->count())->toBe(1);
});

test('dispatchNow flips status to Processing and queues ProcessCampaignJob', function () {
    Bus::fake();

    $user = User::factory()->create();
    $campaign = Campaign::factory()->for($user)->state([
        'status' => CampaignStatus::Draft,
        'scheduled_at' => now()->addHour(),
    ])->create();

    app(CampaignDispatcher::class)->dispatchNow($campaign);

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Processing)
        ->and($campaign->fresh()->scheduled_at)->toBeNull();

    Bus::assertDispatched(
        ProcessCampaignJob::class,
        fn (ProcessCampaignJob $job): bool => $job->campaign->id === $campaign->id,
    );
});

test('schedule marks campaign as Scheduled with the given timestamp', function () {
    Bus::fake();

    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();
    $campaign = Campaign::factory()->for($user)->state([
        'group_id' => $group->id,
        'sender_id' => null,
        'status' => CampaignStatus::Draft,
    ])->create();

    $at = now()->addDay()->startOfHour();

    app(CampaignDispatcher::class)->schedule($campaign, $at);

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Scheduled)
        ->and($campaign->fresh()->scheduled_at->format('Y-m-d H:i'))->toBe($at->format('Y-m-d H:i'));

    Bus::assertNothingDispatched();
});

test('cancel transitions Scheduled to Cancelled and skips remaining pending recipients', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->create(['phone' => '+8801711000001']);
    $contact->groups()->attach($group);

    $campaign = Campaign::factory()->for($user)->state([
        'group_id' => $group->id,
        'status' => CampaignStatus::Scheduled,
        'scheduled_at' => now()->addHour(),
    ])->create();

    app(CampaignDispatcher::class)->snapshotRecipients($campaign);
    $result = app(CampaignDispatcher::class)->cancel($campaign);

    expect($result)->toBeTrue()
        ->and($campaign->fresh()->status)->toBe(CampaignStatus::Cancelled)
        ->and($campaign->fresh()->completed_at)->not->toBeNull()
        ->and(DB::table('campaign_contact')->where('campaign_id', $campaign->id)->where('status', CampaignRecipientStatus::Skipped->value)->count())->toBe(1);
});

test('cancel refuses to act on a Completed campaign', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();
    $campaign = Campaign::factory()->completed()->for($user)->state([
        'group_id' => $group->id,
        'sender_id' => null,
    ])->create();

    $result = app(CampaignDispatcher::class)->cancel($campaign);

    expect($result)->toBeFalse()
        ->and($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
});
