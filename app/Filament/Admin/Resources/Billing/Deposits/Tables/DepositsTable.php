<?php

namespace App\Filament\Admin\Resources\Billing\Deposits\Tables;

use App\Actions\Billing\CompleteDeposit;
use App\Enums\DepositPaymentMethod;
use App\Enums\DepositStatus;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Services\Payment\Data\PaymentResponse;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => $record->user
                        ? CustomerResource::getUrl('view', ['record' => $record->user_id])
                        : null
                    ),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('payment_method')
                    ->label('Method')
                    ->sortable()
                    ->alignCenter()
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('paid_at'),
                TextColumn::make('created_at'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DepositStatus::class),

                SelectFilter::make('payment_method')
                    ->options(DepositPaymentMethod::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === DepositStatus::Pending)
                        ->action(function ($record) {
                            $completeDeposit = app(CompleteDeposit::class);

                            $completeDeposit->handle($record->payment_method->value, new PaymentResponse(
                                successful: true,
                                gatewayDriver: $record->payment_method->value,
                                gatewayStatus: 'completed',
                                gatewayPaymentId: $record->transaction_id,
                            ));
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === DepositStatus::Pending)
                        ->action(function ($record) {
                            $record->update(['status' => DepositStatus::Failed]);

                            Notification::make()
                                ->title('Deposit Rejected')
                                ->body('Your deposit was rejected.')
                                ->danger()
                                ->sendToDatabase($record->user);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
