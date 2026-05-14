<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Schemas;

use App\Enums\SenderType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SenderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sender Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('gateway_id')
                                ->relationship('gateway', 'name')
                                ->required(),
                            TextInput::make('sender_id')
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->options(SenderType::class)
                                ->required(),
                            Toggle::make('is_enabled')
                                ->default(false),
                        ]),
                    ]),
            ]);
    }
}
