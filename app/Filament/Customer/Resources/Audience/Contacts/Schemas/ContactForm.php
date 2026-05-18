<?php

namespace App\Filament\Customer\Resources\Audience\Contacts\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact Details')->schema([
                    TextInput::make('name')->maxLength(255),
                    TextInput::make('phone')->required()->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255),
                    Select::make('groups')
                        ->relationship('groups', 'name')
                        ->multiple()
                        ->createOptionForm([
                            TextInput::make('name')->required(),
                        ]),
                    KeyValue::make('meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
