<?php

namespace App\Filament\Customer\Resources\Billing\Transactions\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transactionable_type')->label('Reference')->badge()->sortable(),
                TextColumn::make('direction')->badge()->sortable(),
                TextColumn::make('amount')->money('BDT')->sortable(),
                TextColumn::make('balance_after')->money('BDT')->label('Balance')->sortable(),
                TextColumn::make('description')->limit(40)->searchable(),
                TextColumn::make('created_at')->dateTime()->label('Date')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ]);
    }
}
