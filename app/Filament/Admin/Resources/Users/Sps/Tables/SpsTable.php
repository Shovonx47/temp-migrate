<?php

namespace App\Filament\Admin\Resources\Users\Sps\Tables;

use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Users\Sps\Actions\DepositAction;
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

class SpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->where('type', 'sp')
                    ->with(['activeTariffAssignment.tariffPlan'])
                    ->withCount('spCustomers');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('balance')
                    ->label('Wallet')
                    ->money('BDT'),
                TextColumn::make('sp_customers_count')
                    ->label('Customers')
                    ->counts('spCustomers'),
                TextColumn::make('activeTariffAssignment.tariffPlan.name')
                    ->label('Wholesale Plan')
                    ->placeholder('No plan')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(UserStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View S P'),
                    DepositAction::make(),
                    EditAction::make()
                        ->label('Edit S P'),
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
