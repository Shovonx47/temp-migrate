<?php

namespace App\Filament\Admin\Resources\Finance\Transactions\Tables;

use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
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
                TextColumn::make('transactionable_type')
                    ->label('Reference')
                    ->badge()
                    ->sortable(),
                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('balance_before')
                    ->label('Before')
                    ->money('BDT')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('balance_after')
                    ->label('After')
                    ->money('BDT')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('created_at'),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->options(TransactionDirection::class),

                SelectFilter::make('transactionable_type')
                    ->label('Reference')
                    ->options(TransactionableType::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
