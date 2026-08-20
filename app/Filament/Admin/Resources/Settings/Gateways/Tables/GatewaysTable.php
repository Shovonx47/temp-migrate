<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GatewaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('driver')
                    ->badge(),
                TextColumn::make('requests_per_second')
                    ->label('RPS')
                    ->sortable(),
                ToggleColumn::make('is_enabled'),
                TextColumn::make('dlr_support')
                    ->label('DLR')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Supported' : 'N/A')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('gatewayOperatorRates_count')
                    ->counts('gatewayOperatorRates')
                    ->label('Rates'),
                TextColumn::make('operator_cost_spent')
                    ->label('Operator Cost')
                    ->state(fn ($record): string => number_format((float) ($record->messages()->where('status', '!=', 'failed')->sum('operator_cost') ?? 0), 2).' ৳')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('gateway_cost_spent')
                    ->label('Dipping Cost')
                    ->state(fn ($record): string => number_format((float) ($record->messages()->where('status', '!=', 'failed')->sum('primary_dipping_cost') ?? 0), 2).' ৳')
                    ->badge()
                    ->color('info'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
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
