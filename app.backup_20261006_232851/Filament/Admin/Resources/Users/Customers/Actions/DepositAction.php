<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class DepositAction
{
    public static function make(?string $name = 'deposit'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedPlus)
            ->label('Make Deposit')
            ->modalIcon(Heroicon::OutlinedCurrencyBangladeshi)
            ->modalHeading('Add Balance to Customer')
            ->modalDescription("Enter the amount to add to the customer's balance.")
            ->modalWidth(Width::Medium)
            ->requiresConfirmation()
            ->visible(fn (User $record) => $record->status->isApproved())
            ->schema([
                \Filament\Schemas\Components\View::make('filament.schemas.components.deposit-customer-header'),
                TextInput::make('amount')
                    ->prefix('BDT')
                    ->label('Amount')
                    ->placeholder('Enter amount (negative to deduct)')
                    ->numeric()
                    ->required()
                    ->rules(['numeric', 'not_in:0'])
                    ->extraInputAttributes([
                        'data-deposit-amount-input' => 'true',
                        'autocomplete' => 'off',
                    ]),
                \Filament\Schemas\Components\View::make('filament.schemas.components.deposit-balance-preview'),
            ])
            ->action(function (array $data, User $record) {
                DB::transaction(function () use ($data, $record) {
                    $amount = (float) $data['amount'];
                    $isNegative = $amount < 0;

                    $record->increment('balance', $amount);

                    /** @var Deposit $deposit */
                    $deposit = $record->deposits()->create([
                        'amount' => $amount,
                        'currency' => 'BDT',
                        'payment_method' => 'manual',
                        'transaction_id' => 'manual-'.uniqid(),
                        'status' => DepositStatus::Completed,
                        'paid_at' => now(),
                    ]);

                    $deposit->transactions()->create([
                        'user_id' => $record->id,
                        'amount' => abs($amount),
                        'direction' => $isNegative ? 'debit' : 'credit',
                        'balance_before' => $record->balance - $amount,
                        'balance_after' => $record->balance,
                        'description' => $isNegative ? 'Admin deducted balance manually' : 'Admin added balance manually',
                    ]);

                    Notification::make()
                        ->title($isNegative ? 'Balance Deducted' : 'Balance Added')
                        ->body($isNegative
                            ? 'Successfully deducted ' . number_format(abs($amount), 2) . " from the customer's balance."
                            : "Successfully added {$amount} to the customer's balance.")
                        ->sendToDatabase($record);

                    Notification::make()
                        ->title($isNegative ? 'Balance Deducted from Customer' : 'Balance Added to Customer')
                        ->body($isNegative
                            ? 'Deducted ' . number_format(abs($amount), 2) . " from {$record->name}'s balance."
                            : "Added {$amount} to {$record->name}'s balance.")
                        ->icon(Heroicon::User)
                        ->send();
                });
            });
    }
}
