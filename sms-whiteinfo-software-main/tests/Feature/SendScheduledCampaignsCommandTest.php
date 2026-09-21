<?php

use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

/**
 * Build a Campaign anchored to an existing user + group, avoiding cascading
 * factory creation that can collide on the users.phone unique index.
 */
function makeBareCampaign(User $user, CampaignStatus $status, ?DateTimeInterface $scheduledAt = null): Campaign
{
    $group = Group::factory()->for($user)->create();

    return Campaign::factory()->for($user)->state([
        'group_id' => $group->id,
        'sender_id' => null,
        'status' => $status,
        'scheduled_at' => $scheduledAt,
    ])->create();
}

test('dispatches campaigns whose scheduled_at has fallen due', function () {
    Bus::fake();

    $user = User::factory()->create();

    $due = makeBareCampaign($user, CampaignStatus::Scheduled, now()->subMinute());
    $future = makeBareCampaign($user, CampaignStatus::Scheduled, now()->addHour());
    $draft = makeBareCampaign($user, CampaignStatus::Draft, now()->subMinute());

    $this->artisan('campaigns:send-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe(CampaignStatus::Processing)
        ->and($future->fresh()->status)->toBe(CampaignStatus::Scheduled)
        ->and($draft->fresh()->status)->toBe(CampaignStatus::Draft);

    Bus::assertDispatched(
        ProcessCampaignJob::class,
        fn (ProcessCampaignJob $job): bool => $job->campaign->id === $due->id,
    );
    Bus::assertDispatchedTimes(ProcessCampaignJob::class, 1);
});

test('reports the number of dispatched campaigns', function () {
    Bus::fake();
    $user = User::factory()->create();

    foreach (range(1, 3) as $_) {
        makeBareCampaign($user, CampaignStatus::Scheduled, now()->subMinute());
    }

    $this->artisan('campaigns:send-scheduled')
        ->expectsOutputToContain('Dispatched 3 scheduled campaign(s).')
        ->assertSuccessful();
});

test('does nothing when there are no scheduled campaigns', function () {
    Bus::fake();

    $this->artisan('campaigns:send-scheduled')
        ->expectsOutputToContain('Dispatched 0 scheduled campaign(s).')
        ->assertSuccessful();

    Bus::assertNothingDispatched();
});
