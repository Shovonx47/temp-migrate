<?php

use App\Filament\Customer\Resources\Billing\Transactions\Pages\ListTransactions;
use App\Filament\Customer\Resources\Billing\Transactions\Pages\ViewTransaction;
use App\Filament\Customer\Resources\Billing\Transactions\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their transactions', function () {
    $user = User::factory()->create();
    $transactions = Transaction::factory()->for($user)->count(3)->create();

    livewire(ListTransactions::class, ['resource' => TransactionResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($transactions);
});

test('customer can view a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->for($user)->create(['description' => 'Test transaction']);

    livewire(ViewTransaction::class, ['record' => $transaction->id, 'resource' => TransactionResource::class])
        ->actingAs($user)
        ->assertSee('Test transaction');
});

test('customer cannot view transactions of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $transaction = Transaction::factory()->for($other)->create();

    livewire(ViewTransaction::class, ['record' => $transaction->id, 'resource' => TransactionResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
