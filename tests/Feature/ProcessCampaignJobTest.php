<?php

use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use App\Services\RecipientFileParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function makeDraftCampaign(?User $user = null): Campaign
{
    $user ??= User::factory()->create();

    return Campaign::factory()
        ->draft()
        ->for($user)
        ->state(['template_id' => null, 'sender_id' => null])
        ->create();
}

function runCampaignJob(Campaign $campaign, string $source, array $config, string $body = 'Hi', array $bodyVariables = []): void
{
    (new ProcessCampaignJob($campaign, $source, $config, $body, $bodyVariables))
        ->handle(app(RecipientFileParser::class));
}

test('manual source creates messages for valid phones and reports invalid ones', function () {
    $campaign = makeDraftCampaign();

    runCampaignJob(
        $campaign,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
            ['phone' => 'not-a-phone', 'values' => []],
            ['phone' => '', 'values' => []],
        ]],
        body: 'Hello there',
    );

    expect(Message::count())->toBe(2);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
    expect($campaign->fresh()->completed_at)->not->toBeNull();

    $reports = Storage::disk('local')->files('campaigns/'.$campaign->id);
    expect($reports)->toHaveCount(1);

    $csv = Storage::disk('local')->get($reports[0]);
    expect($csv)->toContain('not-a-phone');
    expect($csv)->toContain('Invalid phone number format');
    expect($csv)->toContain('Phone number missing');
});

test('manual source completes cleanly when every recipient is valid', function () {
    $campaign = makeDraftCampaign();

    runCampaignJob(
        $campaign,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => '01711000002', 'values' => []],
        ]],
    );

    expect(Message::count())->toBe(2);
    expect(Storage::disk('local')->files('campaigns/'.$campaign->id))->toBeEmpty();
});

test('substitutes body variables per recipient', function () {
    $campaign = makeDraftCampaign();

    runCampaignJob(
        $campaign,
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
    $campaign = makeDraftCampaign();

    $csv = "phone,first_name\n01711000001,Rahim\n01711000002,Karim\n";
    Storage::disk('local')->put('campaigns/'.$campaign->id.'/source.csv', $csv);

    runCampaignJob(
        $campaign,
        'upload',
        [
            'disk' => 'local',
            'file_path' => 'campaigns/'.$campaign->id.'/source.csv',
            'mapping' => [
                'phone' => 'phone',
                'variables' => ['name' => 'first_name'],
            ],
        ],
        body: 'Hi {name}',
        bodyVariables: ['name'],
    );

    expect(Message::count())->toBe(2);
    expect(Message::pluck('body')->all())->toBe(['Hi Rahim', 'Hi Karim']);
});

test('group source pulls contacts via the relationship and skips blocked ones', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    $contacts = Contact::factory()->count(3)->for($user)->create();
    $blocked = Contact::factory()->blocked()->for($user)->create([
        'phone' => '+8801999999999',
    ]);
    $group->contacts()->sync([$contacts[0]->id, $contacts[1]->id, $contacts[2]->id, $blocked->id]);

    $campaign = makeDraftCampaign($user);

    runCampaignJob(
        $campaign,
        'group',
        ['group_ids' => [$group->id]],
        body: 'Hi',
    );

    expect(Message::count())->toBe(3);
    expect(Message::where('status', MessageStatus::Pending)->count())->toBe(3);
});

test('writes a database notification for the user when the campaign completes', function () {
    $user = User::factory()->create();
    $campaign = makeDraftCampaign($user);

    runCampaignJob(
        $campaign,
        'manual',
        ['recipients' => [
            ['phone' => '01711000001', 'values' => []],
            ['phone' => 'bad', 'values' => []],
        ]],
    );

    expect($user->notifications()->count())->toBe(1);
});

test('missing upload file is treated as zero recipients, not a fatal error', function () {
    $campaign = makeDraftCampaign();

    runCampaignJob(
        $campaign,
        'upload',
        [
            'disk' => 'local',
            'file_path' => 'campaigns/'.$campaign->id.'/missing.csv',
            'mapping' => ['phone' => 'phone', 'variables' => []],
        ],
    );

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
    expect(Message::count())->toBe(0);
});
