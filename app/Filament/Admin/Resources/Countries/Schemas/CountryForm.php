<?php

namespace App\Filament\Admin\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Bangladesh'),
                    TextInput::make('code')
                        ->label('Country code')
                        ->required()
                        ->maxLength(8)
                        ->placeholder('880')
                        ->helperText('Phone country code (digits only).'),
                    TextInput::make('iso_alpha2')
                        ->label('ISO Alpha-2')
                        ->maxLength(2)
                        ->placeholder('BD'),
                    Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ]),
            ]);
    }
}
