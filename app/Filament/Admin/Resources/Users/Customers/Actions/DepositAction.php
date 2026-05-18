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
            ->visible(fn (User $record) => $record->approval->isApproved())
            ->schema([
                TextInput::make('amount')
                    ->prefix('BDT')
                    ->label('Amount')
                    ->placeholder('Enter amount')
                    ->numeric()
                    ->required()
                    ->minValue(0),
            ])
            ->action(function (array $data, User $record) {
                DB::transaction(function () use ($data, $record) {
                    $record->increment('balance', $data['amount']);

                    /** @var Deposit $deposit */
                    $deposit = $record->deposits()->create([
                        'amount' => $data['amount'],
                        'currency' => 'BDT',
                        'payment_method' => 'manual',
                        'transaction_id' => 'manual-'.uniqid(),
                        'status' => DepositStatus::Completed,
                        'paid_at' => now(),
                    ]);

                    $deposit->transactions()->create([
                        'user_id' => $record->id,
                        'amount' => $data['amount'],
                        'direction' => 'credit',
                        'balance_before' => $record->balance - $data['amount'],
                        'balance_after' => $record->balance,
                        'description' => 'Admin added balance manually',
                    ]);

                    Notification::make()
                        ->title('Balance Added')
                        ->body("Successfully added {$data['amount']} to the customer's balance.")
                        ->sendToDatabase($record);

                    Notification::make()
                        ->title('Balance Added to Customer')
                        ->body("Added {$data['amount']} to {$record->name}'s balance.")
                        ->icon(Heroicon::User)
                        ->send();
                });
            });
    }
}
