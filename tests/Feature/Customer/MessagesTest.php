<?php

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ListMessages;
use App\Filament\Customer\Resources\Messaging\Messages\Pages\ViewMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their messages', function () {
    $user = User::factory()->create();
    $messages = Message::factory()->for($user)->count(3)->create();

    livewire(ListMessages::class, ['resource' => MessageResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($messages);
});

test('customer can view a message', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->create();

    livewire(ViewMessage::class, ['record' => $message->id, 'resource' => MessageResource::class])
        ->actingAs($user)
        ->assertSee($message->uid);
});

test('customer cannot view messages of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $message = Message::factory()->for($other)->create();

    livewire(ViewMessage::class, ['record' => $message->id, 'resource' => MessageResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
