<?php

namespace App\Filament\Admin\Resources\Billing\Deposits\Tables;

use App\Enums\DepositPaymentMethod;
use App\Enums\DepositStatus;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
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
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
