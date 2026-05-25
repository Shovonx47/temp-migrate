<?php

namespace App\Filament\Customer\Resources\Developer\AccessTokens\Schemas;

use Filament\Forms\Components\DatePicker;
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
                DatePicker::make('expires_at')
                    ->label('Expiration Date')
                    ->placeholder('Select an expiration date')
                    ->native(false),
            ]);
    }
}
