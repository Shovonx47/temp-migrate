<?php

namespace App\Filament\Customer\Resources\Developer\AccessTokens\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccessTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Token Name')
                    ->placeholder('Enter a name for the token')
                    ->required()
                    ->maxLength(255),
                Select::make('expires_at')
                    ->label('Expiration Date')
                    ->options([
                        1 => '1 Day',
                        7 => '7 Days',
                        30 => '1 Month',
                        90 => '3 Months',
                        180 => '6 Months',
                        365 => '1 Year',
                        'never' => 'Never',
                    ])
                    ->default(30)
                    ->required()
                    ->visibleOn('create'),
            ]);
    }
}
