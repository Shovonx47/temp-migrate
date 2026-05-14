<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gateway Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('driver')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('daily_limit')
                                ->numeric(),
                            TextInput::make('monthly_limit')
                                ->numeric(),
                            Toggle::make('is_enabled')
                                ->default(false),
                            Toggle::make('supports_bulk')
                                ->default(false),
                        ]),
                    ]),
                Section::make('Configuration')
                    ->schema([
                        Grid::make(2)->schema([
                            KeyValue::make('credentials')
                                ->label('Credentials')
                                ->columnSpan(1),
                            KeyValue::make('config')
                                ->label('Config')
                                ->columnSpan(1),
                        ]),
                    ]),
            ]);
    }
}
