<?php

namespace App\Filament\Admin\Resources\Settings\Operators\Tables;

use App\Models\Country;
use App\Services\Analytics\ProfitCalculationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OperatorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')->label('Country')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('codes')
                    ->label('Codes')
                    ->state(fn ($record): string => implode(', ', (array) $record->codes)),
                TextColumn::make('masking_price')
                    ->label('Masking')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—'),
                TextColumn::make('non_masking_price')
                    ->label('Non-masking')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—'),
                TextColumn::make('operator_cost_spent')
                    ->label('Operator Cost (Spent)')
                    ->state(function ($record): string {
                        $breakdown = app(ProfitCalculationService::class)->getOperatorBreakdown(now()->subYears(10), now());
                        $item = collect($breakdown)->firstWhere('operator', $record->name);

                        return number_format((float) ($item['operator_cost'] ?? 0), 2).' ৳';
                    })
                    ->badge()
                    ->color('warning'),
                TextColumn::make('gateway_cost_spent')
                    ->label('InfoZillion Cost (Spent)')
                    ->state(function ($record): string {
                        $breakdown = app(ProfitCalculationService::class)->getOperatorBreakdown(now()->subYears(10), now());
                        $item = collect($breakdown)->firstWhere('operator', $record->name);

                        return number_format((float) ($item['gateway_cost'] ?? 0), 2).' ৳';
                    })
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label('Country')
                    ->options(fn () => Country::pluck('name', 'id')),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
