<?php

use App\Filament\Customer\Resources\Settings\Senders\Pages\ListSenders;
use App\Filament\Customer\Resources\Settings\Senders\Pages\ViewSender;
use App\Filament\Customer\Resources\Settings\Senders\SenderResource;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their senders', function () {
    $user = User::factory()->create();
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    livewire(ListSenders::class, ['resource' => SenderResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords([$sender]);
});

test('customer can view their sender', function () {
    $user = User::factory()->create();
    $sender = Sender::factory()->create();
    $sender->users()->attach($user);

    livewire(ViewSender::class, ['record' => $sender->id, 'resource' => SenderResource::class])
        ->actingAs($user)
        ->assertSee($sender->sender_id);
});

test('customer cannot view senders not assigned to them', function () {
    $user = User::factory()->create();
    $sender = Sender::factory()->create();

    livewire(ViewSender::class, ['record' => $sender->id, 'resource' => SenderResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
