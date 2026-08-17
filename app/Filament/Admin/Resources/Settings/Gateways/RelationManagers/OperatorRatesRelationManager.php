<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\RelationManagers;

use App\Models\Operator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OperatorRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'gatewayOperatorRates';

    protected static ?string $title = 'Operator Rates';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('operator.country.name')
                    ->label('Country')
                    ->sortable(),
                TextColumn::make('operator.non_masking_price')
                    ->label('Operator Base Rate')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—'),
                TextColumn::make('non_masking_price')
                    ->label('InfoZillion Rate')
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—'),
                TextColumn::make('operator_spent')
                    ->label('Operator Spent')
                    ->state(fn ($record): string => number_format((float) $record->operator_spent, 2).' ৳')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('gateway_spent')
                    ->label('InfoZillion Spent')
                    ->state(fn ($record): string => number_format((float) $record->gateway_spent, 2).' ৳')
                    ->badge()
                    ->color('info'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('operator_id')
                ->label('Operator')
                ->options(Operator::query()->active()->with('country')->get()->mapWithKeys(
                    fn ($op) => [$op->id => $op->name.' ('.$op->country->name.')']
                ))
                ->searchable()
                ->required(),
            TextInput::make('masking_price')
                ->label('Masking Price (per part)')
                ->numeric()
                ->nullable(),
            TextInput::make('non_masking_price')
                ->label('Non-Masking Price (per part)')
                ->numeric()
                ->nullable(),
        ]);
    }
}
