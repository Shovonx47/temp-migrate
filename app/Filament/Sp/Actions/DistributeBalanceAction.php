<?php

namespace App\Filament\Sp\Actions;

use App\Actions\Billing\DistributeBalance;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

class DistributeBalanceAction
{
    public static function make(?string $name = 'distributeBalance'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->label('Distribute Balance')
            ->modalIcon(Heroicon::OutlinedBanknotes)
            ->modalHeading('Distribute Balance to Customer')
            ->modalDescription('Move balance from your S P wallet to a customer wallet.')
            ->modalWidth(Width::Medium)
            ->schema([
                Select::make('customer_id')
                    ->label('Customer')
                    ->placeholder('Select a customer')
                    ->options(function (): array {
                        $user = auth()->user();
                        $sp = ($user?->type?->isSp() && $user?->sp_id) ? User::find($user->sp_id) : $user;

                        return $sp
                            ?->spCustomers()
                            ->where('status', UserStatus::Approved->value)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all() ?? [];
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('amount')
                    ->label('Amount')
                    ->prefix('BDT')
                    ->placeholder('Enter amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01),
                TextInput::make('note')
                    ->label('Note')
                    ->placeholder('Optional note')
                    ->maxLength(255)
                    ->nullable(),
            ])
            ->action(function (array $data): void {
                /** @var User $actingUser */
                $actingUser = auth()->user();
                $sp = ($actingUser->type->isSp() && $actingUser->sp_id) ? User::findOrFail($actingUser->sp_id) : $actingUser;
                $customer = User::query()->findOrFail($data['customer_id']);

                try {
                    app(DistributeBalance::class)->handle(
                        $sp,
                        $customer,
                        (float) $data['amount'],
                        $data['note'] ?? null,
                        $actingUser,
                    );

                    Notification::make()
                        ->title('Balance Distributed')
                        ->body("Distributed {$data['amount']} BDT to {$customer->name}.")
                        ->success()
                        ->send();
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->title('Distribution Failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
