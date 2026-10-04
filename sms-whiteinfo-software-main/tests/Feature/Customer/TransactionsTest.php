<?php

use App\Filament\Customer\Resources\Billing\Transactions\Pages\ListTransactions;
use App\Filament\Customer\Resources\Billing\Transactions\Pages\ViewTransaction;
use App\Filament\Customer\Resources\Billing\Transactions\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('customer'));
});

test('customer can list their transactions', function () {
    $user = User::factory()->create();
    $transactions = Transaction::factory()->for($user)->count(3)->create();

    actingAs($user, 'customer');

    Livewire::test(ListTransactions::class, ['resource' => TransactionResource::class])
        ->assertCanSeeTableRecords($transactions);
});

test('customer can view a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->for($user)->create(['description' => 'Test transaction']);

    actingAs($user, 'customer');

    Livewire::test(ViewTransaction::class, ['record' => $transaction->id, 'resource' => TransactionResource::class])
        ->assertSee('Test transaction');
});

test('customer cannot view transactions of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $transaction = Transaction::factory()->for($other)->create();

    actingAs($user, 'customer');

    expect(fn () => Livewire::test(ViewTransaction::class, ['record' => $transaction->id, 'resource' => TransactionResource::class]))
        ->toThrow(ModelNotFoundException::class);
});
