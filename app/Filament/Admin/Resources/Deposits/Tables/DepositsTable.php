<?php

namespace App\Filament\Admin\Resources\Deposits\Tables;

use App\Enums\DepositStatus;
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
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('Currency'),

                TextColumn::make('payment_method')
                    ->label('Method'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('paid_at'),
                TextColumn::make('created_at'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DepositStatus::class),

                SelectFilter::make('payment_method')
                    ->options([
                        'piprapay' => 'PipraPay',
                        'manual' => 'Manual',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
