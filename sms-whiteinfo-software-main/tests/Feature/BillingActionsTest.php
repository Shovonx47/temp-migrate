<?php

use App\Actions\Billing\CreditBalance;
use App\Actions\Billing\DebitBalance;
use App\Enums\TransactionDirection;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('credit balance updates the user and records a ledger entry', function () {
    $user = User::factory()->create(['balance' => 100]);
    $deposit = Deposit::factory()->for($user)->pending()->create(['amount' => 50]);

    $transaction = app(CreditBalance::class)->handle(
        user: $user,
        amount: 50,
        transactionable: $deposit,
        description: 'Deposit via test',
    );

    expect((float) $user->refresh()->balance)->toBe(150.0)
        ->and($transaction->transactionable->is($deposit))->toBeTrue()
        ->and($transaction->direction)->toBe(TransactionDirection::Credit)
        ->and((float) $transaction->balance_before)->toBe(100.0)
        ->and((float) $transaction->balance_after)->toBe(150.0);
});

test('debit balance updates the user and records a ledger entry', function () {
    $user = User::factory()->create(['balance' => 100]);
    $deposit = Deposit::factory()->for($user)->completed()->create(['amount' => 30]);

    $transaction = app(DebitBalance::class)->handle(
        user: $user,
        amount: 30,
        transactionable: $deposit,
        description: 'Refund via test',
    );

    expect((float) $user->refresh()->balance)->toBe(70.0)
        ->and($transaction->transactionable->is($deposit))->toBeTrue()
        ->and($transaction->direction)->toBe(TransactionDirection::Debit)
        ->and((float) $transaction->balance_before)->toBe(100.0)
        ->and((float) $transaction->balance_after)->toBe(70.0);
});

test('debit balance rejects insufficient balance without writing a transaction', function () {
    $user = User::factory()->create(['balance' => 10]);
    $deposit = Deposit::factory()->for($user)->completed()->create(['amount' => 30]);

    try {
        app(DebitBalance::class)->handle(
            user: $user,
            amount: 30,
            transactionable: $deposit,
        );
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Insufficient balance.');
    }

    expect((float) $user->refresh()->balance)->toBe(10.0)
        ->and($deposit->transactions()->count())->toBe(0);
});
