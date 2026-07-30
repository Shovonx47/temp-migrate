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
                TextColumn::make('masking_price')
                    ->label('Masking Price')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('non_masking_price')
                    ->label('Non-Masking Price')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
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
