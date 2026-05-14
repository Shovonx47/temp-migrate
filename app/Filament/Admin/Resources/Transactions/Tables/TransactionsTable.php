<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
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
                    ->searchable(),

                TextColumn::make('transactionable_type')
                    ->label('Reference')
                    ->badge()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->sortable(),

                TextColumn::make('balance_before')
                    ->label('Balance Before')
                    ->money('BDT')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->money('BDT')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->options(TransactionDirection::class),

                SelectFilter::make('transactionable_type')
                    ->label('Reference')
                    ->options(TransactionableType::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
