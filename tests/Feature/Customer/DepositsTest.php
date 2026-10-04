<?php

use App\Filament\Customer\Resources\Billing\Deposits\DepositResource;
use App\Filament\Customer\Resources\Billing\Deposits\Pages\ListDeposits;
use App\Filament\Customer\Resources\Billing\Deposits\Pages\ViewDeposit;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their deposits', function () {
    $user = User::factory()->create();
    $deposits = Deposit::factory()->for($user)->count(3)->create();

    livewire(ListDeposits::class, ['resource' => DepositResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($deposits);
});

test('customer can view a deposit', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->for($user)->create();

    livewire(ViewDeposit::class, ['record' => $deposit->id, 'resource' => DepositResource::class])
        ->actingAs($user)
        ->assertSee($deposit->transaction_id);
});

test('customer cannot view deposits of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $deposit = Deposit::factory()->for($other)->create();

    livewire(ViewDeposit::class, ['record' => $deposit->id, 'resource' => DepositResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
