<?php

namespace App\Filament\Sp\Resources\Customers\Actions;

use App\Actions\Billing\DistributeBalance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

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
                View::make('filament.schemas.components.deposit-customer-header'),
                TextInput::make('amount')
                    ->prefix('BDT')
                    ->label('Amount')
                    ->placeholder('Enter amount (negative to reverse)')
                    ->numeric()
                    ->required()
                    ->rules(['numeric', 'not_in:0'])
                    ->extraInputAttributes([
                        'data-deposit-amount-input' => 'true',
                        'autocomplete' => 'off',
                    ]),
                TextInput::make('note')
                    ->label('Note')
                    ->placeholder('Optional note (e.g. Balance top up)')
                    ->maxLength(255)
                    ->nullable(),
                View::make('filament.schemas.components.deposit-balance-preview'),
            ])
            ->action(function (array $data, User $record): void {
                /** @var User $actingUser */
                $actingUser = auth()->user();
                $sp = ($actingUser->type->isSp() && $actingUser->sp_id) ? User::findOrFail($actingUser->sp_id) : $actingUser;
                $amount = (float) $data['amount'];
                $isNegative = $amount < 0;

                try {
                    app(DistributeBalance::class)->handle(
                        $sp,
                        $record,
                        $amount,
                        $data['note'] ?? null,
                        $actingUser,
                    );

                    Notification::make()
                        ->title($isNegative ? 'Balance Deducted from Customer' : 'Balance Added to Customer')
                        ->body($isNegative
                            ? 'Successfully deducted BDT ' . number_format(abs($amount), 2) . " from {$record->name}'s balance."
                            : "Successfully added BDT {$data['amount']} to {$record->name}'s balance.")
                        ->success()
                        ->send();
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->title($isNegative ? 'Reversal Failed' : 'Deposit Failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
