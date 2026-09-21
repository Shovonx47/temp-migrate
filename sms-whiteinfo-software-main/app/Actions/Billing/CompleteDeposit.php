<?php

namespace App\Actions\Billing;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\Payment\Data\PaymentResponse;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class CompleteDeposit
{
    public function __construct(
        private CreditBalance $creditBalance,
        private DebitBalance $debitBalance,
    ) {}

    /**
     * Returns true if the deposit was completed by this call,
     * false if already completed or not applicable.
     *
     * @throws Throwable
     */
    public function handle(string $gateway, PaymentResponse $response): bool
    {
        return DB::transaction(function () use ($gateway, $response) {
            $deposit = Deposit::query()
                ->wherePaymentMethod($gateway)
                ->whereTransactionId($response->gatewayPaymentId)
                ->lockForUpdate()
                ->first();

            if (! $deposit) {
                return false;
            }

            if ($deposit->status === DepositStatus::Completed) {
                return false;
            }

            if (! $response->successful) {
                return false;
            }

            $deposit->update([
                'status' => DepositStatus::Completed,
                'paid_at' => now(),
                'meta' => $response->raw,
            ]);

            $user = $deposit->user;

            if ($user->type->isCustomer() && $user->sp_id) {
                $sp = $user->sp;
                if ($sp) {
                    $this->debitBalance->handle(
                        user: $sp,
                        amount: $deposit->amount,
                        transactionable: $deposit,
                        description: "Customer top-up: {$user->name} (#{$deposit->id})"
                    );
                }
            }

            $this->creditBalance->handle(
                user: $user,
                amount: $deposit->amount,
                transactionable: $deposit,
                description: "Deposit of {$deposit->amount} via {$deposit->payment_method->value}"
            );

            DB::afterCommit(function () use ($deposit, $user) {
                Notification::make()
                    ->icon(Heroicon::OutlinedCurrencyBangladeshi)
                    ->title('Deposit Completed')
                    ->body("Your deposit of {$deposit->amount} has been completed.")
                    ->sendToDatabase($user);

                if ($user->type->isCustomer() && $user->sp_id && $user->sp) {
                    Notification::make()
                        ->icon(Heroicon::OutlinedCurrencyBangladeshi)
                        ->title('Customer Top-up')
                        ->body("Customer {$user->name} topped up {$deposit->amount} BDT. Deducted from your balance.")
                        ->sendToDatabase($user->sp);
                }
            });

            return true;
        }, attempts: 3);
    }
}
