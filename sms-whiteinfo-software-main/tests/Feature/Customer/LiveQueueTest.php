<?php

use App\Enums\BatchStatus;
use App\Enums\MessageStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Customer\Pages\LiveQueuePage;
use App\Filament\Customer\Widgets\CustomerLiveMessageStreamWidget;
use App\Filament\Customer\Widgets\CustomerLiveQueueStatsWidget;
use App\Models\Batch;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->customer = User::factory()->create([
        'type' => UserType::Customer,
        'status' => UserStatus::Approved,
        'front_nid' => 'nid/front.jpg',
        'back_nid' => 'nid/back.jpg',
    ]);

    $this->otherCustomer = User::factory()->create([
        'type' => UserType::Customer,
        'status' => UserStatus::Approved,
        'front_nid' => 'nid/front.jpg',
        'back_nid' => 'nid/back.jpg',
    ]);
});

test('live queue page registers correct widgets', function () {
    actingAs($this->customer, 'customer');

    $widgets = (new LiveQueuePage)->getWidgets();

    expect($widgets)->toContain(
        CustomerLiveQueueStatsWidget::class,
        CustomerLiveMessageStreamWidget::class,
    );
});

test('live queue page is accessible to authenticated customer', function () {
    actingAs($this->customer, 'customer');

    $this->get('/messaging/live-queue')->assertOk();
});

test('stats widget scopes queue count to authenticated customer only', function () {
    actingAs($this->customer, 'customer');

    // Own messages
    Message::factory()->count(3)->create([
        'user_id' => $this->customer->id,
        'status' => MessageStatus::Pending,
    ]);

    // Another customer's messages — should NOT be counted
    Message::factory()->count(5)->create([
        'user_id' => $this->otherCustomer->id,
        'status' => MessageStatus::Pending,
    ]);

    $widget = new CustomerLiveQueueStatsWidget;
    $stats = $widget->getStats();

    // First stat is "In Queue Now"
    $inQueueStat = $stats[0];

    // The value should only count our 3 messages, not the other 5
    expect($inQueueStat->getValue())->toBe('3');
});

test('message stream widget only returns authenticated customer messages', function () {
    actingAs($this->customer, 'customer');

    // Own pending message
    Message::factory()->create([
        'user_id' => $this->customer->id,
        'status' => MessageStatus::Pending,
    ]);

    // Another customer's pending message — must be excluded
    Message::factory()->create([
        'user_id' => $this->otherCustomer->id,
        'status' => MessageStatus::Pending,
    ]);

    $messages = livewire(CustomerLiveMessageStreamWidget::class)
        ->instance()
        ->getMessages();

    expect($messages)->toHaveCount(1);
});

test('message stream widget search filters by recipient', function () {
    actingAs($this->customer, 'customer');

    Message::factory()->create([
        'user_id' => $this->customer->id,
        'status' => MessageStatus::Pending,
        'to' => '+8801711000001',
    ]);

    Message::factory()->create([
        'user_id' => $this->customer->id,
        'status' => MessageStatus::Pending,
        'to' => '+8801799999999',
    ]);

    $messages = livewire(CustomerLiveMessageStreamWidget::class)
        ->set('search', '01711')
        ->instance()
        ->getMessages();

    expect($messages)->toHaveCount(1);
});

test('message stream widget type filter quick excludes bulk messages', function () {
    actingAs($this->customer, 'customer');

    $batch = Batch::factory()->create([
        'user_id' => $this->customer->id,
        'status' => BatchStatus::Processing,
    ]);

    // Quick message (no batch_id)
    Message::factory()->create([
        'user_id' => $this->customer->id,
        'status' => MessageStatus::Pending,
        'batch_id' => null,
    ]);

    // Bulk message (has batch_id)
    Message::factory()->create([
        'user_id' => $this->customer->id,
        'status' => MessageStatus::Pending,
        'batch_id' => $batch->id,
    ]);

    $messages = livewire(CustomerLiveMessageStreamWidget::class)
        ->set('typeFilter', 'quick')
        ->instance()
        ->getMessages();

    expect($messages)->toHaveCount(1)
        ->and($messages->first()['is_bulk'])->toBeFalse();
});

test('active batches are scoped to authenticated customer', function () {
    actingAs($this->customer, 'customer');

    Batch::factory()->create([
        'user_id' => $this->customer->id,
        'status' => BatchStatus::Processing,
    ]);

    Batch::factory()->create([
        'user_id' => $this->otherCustomer->id,
        'status' => BatchStatus::Processing,
    ]);

    $batches = livewire(CustomerLiveMessageStreamWidget::class)
        ->instance()
        ->getActiveBatches();

    expect($batches)->toHaveCount(1);
});

test('message stream widget can be paused and resumed', function () {
    actingAs($this->customer, 'customer');

    livewire(CustomerLiveMessageStreamWidget::class)
        ->assertSet('isPaused', false)
        ->call('togglePause')
        ->assertSet('isPaused', true)
        ->call('togglePause')
        ->assertSet('isPaused', false);
});

test('message stream widget can switch between stream and table view', function () {
    actingAs($this->customer, 'customer');

    livewire(CustomerLiveMessageStreamWidget::class)
        ->assertSet('viewMode', 'stream')
        ->call('setViewMode', 'table')
        ->assertSet('viewMode', 'table')
        ->call('setViewMode', 'stream')
        ->assertSet('viewMode', 'stream');
});
