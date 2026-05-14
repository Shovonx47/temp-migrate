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
                TextColumn::make('amount')->money('BDT')->sortable(),
                TextColumn::make('payment_method')->badge()->sortable(),
                TextColumn::make('transaction_id')->label('PipaPay ID')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ]);
    }
}
