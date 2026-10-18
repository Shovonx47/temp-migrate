<?php

namespace App\Filament\Sp\Resources\Customers\Tables;

use App\Filament\Sp\Resources\Customers\Actions\DepositAction;
use App\Enums\UserStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->with(['activeTariffAssignment.tariffPlan']);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('BDT'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('activeTariffAssignment.tariffPlan.name')
                    ->label('Plan')
                    ->placeholder('No plan assigned')
                    ->badge()
                    ->color('info')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(UserStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Customer'),
                    DepositAction::make(),
                    EditAction::make()
                        ->label('Edit Customer'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
