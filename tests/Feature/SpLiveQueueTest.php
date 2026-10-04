<?php

use App\Enums\BatchStatus;
use App\Enums\MessageStatus;
use App\Enums\UserType;
use App\Filament\Sp\Pages\LiveQueuePage;
use App\Filament\Sp\Widgets\SpLiveMessageStreamWidget;
use App\Filament\Sp\Widgets\SpLiveQueueStatsWidget;
use App\Models\Batch;
use App\Models\Message;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('sp can view live queue page on sp panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['phone' => '+8801711111111']);
    actingAs($sp, 'sp');

    Livewire::test(LiveQueuePage::class)
        ->assertSuccessful();
});

test('sp live queue stats widget renders accurately with customer data', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['phone' => '+8801711111111']);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'sp_id' => $sp->id,
        'phone' => '+8801722222222',
    ]);

    // Create active message in queue
    Message::factory()->create([
        'user_id' => $customer->id,
        'campaign_id' => null,
        'status' => MessageStatus::Pending,
        'cost' => 0.50,
        'rate' => 0.50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create active batch
    Batch::factory()->create([
        'user_id' => $customer->id,
        'status' => BatchStatus::Processing,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($sp, 'sp');

    Livewire::test(SpLiveQueueStatsWidget::class)
        ->assertSuccessful();
});

test('sp live message stream strictly isolates to sp customers only', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp1 = User::factory()->sp()->create(['phone' => '+8801711111111']);
    $customer1 = User::factory()->create([
        'type' => UserType::Customer,
        'name' => 'Alice Customer SP1',
        'sp_id' => $sp1->id,
        'phone' => '+8801722222222',
    ]);

    $sp2 = User::factory()->sp()->create(['phone' => '+8801733333333']);
    $customer2 = User::factory()->create([
        'type' => UserType::Customer,
        'name' => 'Bob Customer SP2',
        'sp_id' => $sp2->id,
        'phone' => '+8801744444444',
    ]);

    $msg1 = Message::factory()->create([
        'user_id' => $customer1->id,
        'campaign_id' => null,
        'to' => '+8801700000001',
        'body' => 'Secret SP1 Message',
        'status' => MessageStatus::Pending,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $msg2 = Message::factory()->create([
        'user_id' => $customer2->id,
        'campaign_id' => null,
        'to' => '+8801700000002',
        'body' => 'Secret SP2 Message',
        'status' => MessageStatus::Pending,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($sp1, 'sp');

    $widget = new SpLiveMessageStreamWidget();
    $messages = $widget->getMessages();

    expect($messages->pluck('id')->all())->toContain($msg1->id);
    expect($messages->pluck('id')->all())->not->toContain($msg2->id);
});

test('sp can purge stale messages stuck for more than 8 minutes', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['phone' => '+8801711111111']);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'sp_id' => $sp->id,
        'phone' => '+8801722222222',
    ]);

    $staleTime = Carbon::now()->subMinutes(12);

    $staleMessage = Message::factory()->create([
        'user_id' => $customer->id,
        'campaign_id' => null,
        'status' => MessageStatus::Pending,
        'updated_at' => $staleTime,
    ]);

    $freshMessage = Message::factory()->create([
        'user_id' => $customer->id,
        'campaign_id' => null,
        'status' => MessageStatus::Pending,
        'updated_at' => Carbon::now()->subMinutes(2),
    ]);

    actingAs($sp, 'sp');

    Livewire::test(SpLiveMessageStreamWidget::class)
        ->call('purgeStaleQueue')
        ->assertNotified();

    expect(Message::withoutGlobalScopes()->find($staleMessage->id)->status)
        ->toBe(MessageStatus::Failed);

    expect(Message::withoutGlobalScopes()->find($freshMessage->id)->status)
        ->toBe(MessageStatus::Pending);
});

test('sp can retry failed message and delete pending message from queue', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['phone' => '+8801711111111']);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'sp_id' => $sp->id,
        'phone' => '+8801722222222',
    ]);

    $pendingMessage = Message::factory()->create([
        'user_id' => $customer->id,
        'campaign_id' => null,
        'status' => MessageStatus::Pending,
    ]);

    $failedMessage = Message::factory()->create([
        'user_id' => $customer->id,
        'campaign_id' => null,
        'status' => MessageStatus::Failed,
        'failed_reason' => 'Network error',
    ]);

    actingAs($sp, 'sp');

    Livewire::test(SpLiveMessageStreamWidget::class)
        ->call('deleteMessage', $pendingMessage->id)
        ->assertNotified();

    expect(Message::withoutGlobalScopes()->find($pendingMessage->id)->status)
        ->toBe(MessageStatus::Failed);

    Livewire::test(SpLiveMessageStreamWidget::class)
        ->call('retrySingleMessage', $failedMessage->id)
        ->assertNotified();

    $reloaded = Message::withoutGlobalScopes()->find($failedMessage->id);
    expect($reloaded->status)->toBe(MessageStatus::Pending);
    expect($reloaded->failed_reason)->toBeNull();
});
