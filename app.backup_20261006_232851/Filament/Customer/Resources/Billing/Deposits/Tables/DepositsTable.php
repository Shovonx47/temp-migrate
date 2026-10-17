<?php

namespace App\Filament\Customer\Resources\Billing\Deposits\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->sortable(),
                TextColumn::make('transaction_id')
                    ->label('Trx ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('paid_at'),
                TextColumn::make('created_at'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ]);
    }
}
