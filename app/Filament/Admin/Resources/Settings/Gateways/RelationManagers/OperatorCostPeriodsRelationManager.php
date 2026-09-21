<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OperatorCostPeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'operatorCostPeriods';

    protected static ?string $title = 'Billing Period Operator Costs';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_start')
                    ->label('Period')
                    ->state(fn ($record) => $record->period_start?->format('M d, Y').' — '.$record->period_end?->format('M d, Y'))
                    ->sortable(['period_start']),
                TextColumn::make('manual_cost')
                    ->label('Authoritative Cost (Manual)')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('৳')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('api_cost')
                    ->label('API Check Cost')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('৳')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('cost_variance')
                    ->label('Cost Variance (Delta)')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('৳')
                    ->badge(fn ($record) => ($record->cost_variance ?? 0.0) != 0.0)
                    ->color(fn ($record) => match (true) {
                        $record->api_cost === null => 'gray',
                        ($record->cost_variance ?? 0.0) == 0.0 => 'success',
                        abs((float) $record->cost_variance) > 0 => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('dlr_delivered_count')
                    ->label('Delivered Count')
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('dlr_api_delivered_count')
                    ->label('API Delivered')
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('dlr_variance')
                    ->label('DLR Delta')
                    ->badge(fn ($record) => ($record->dlr_variance ?? 0) != 0)
                    ->color(fn ($record) => match (true) {
                        $record->dlr_api_delivered_count === null => 'gray',
                        ($record->dlr_variance ?? 0) == 0 => 'success',
                        default => 'warning',
                    })
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Add Operator Cost Period'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Billing Period')
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('period_start')
                            ->label('Period Start Date')
                            ->required(),
                        DatePicker::make('period_end')
                            ->label('Period End Date')
                            ->required(),
                    ]),
                ]),
            Section::make('Operator Costs')
                ->description('Manual-entered cost is the authoritative billing source. API-fetched cost is for reconciliation only.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('manual_cost')
                            ->label('Manual Operator Cost (৳)')
                            ->numeric()
                            ->required()
                            ->helperText('Primary authoritative cost entered from operator invoice/bill.'),
                        TextInput::make('api_cost')
                            ->label('API Cost (৳ - Optional verification)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Cross-check cost fetched or reported from gateway API.'),
                    ]),
                ]),
            Section::make('DLR Reconciliation (Optional)')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('dlr_delivered_count')
                            ->label('Internal Delivered SMS Count')
                            ->numeric()
                            ->nullable(),
                        TextInput::make('dlr_api_delivered_count')
                            ->label('API Delivered SMS Count')
                            ->numeric()
                            ->nullable(),
                    ]),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->columnSpanFull()
                        ->nullable(),
                ]),
        ]);
    }
}
