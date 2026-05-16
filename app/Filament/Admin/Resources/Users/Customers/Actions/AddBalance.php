<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class AddBalance
{
    public static function make(?string $name = 'add_balance'): Action
    {
        return Action::make($name)
            ->label('Add Balance')
            ->modalHeading('Add Balance')
            ->modalDescription("Enter the amount to add to the customer's balance.")
            ->schema([
                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->required()
                    ->minValue(50),
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
                });
            });
    }
}
