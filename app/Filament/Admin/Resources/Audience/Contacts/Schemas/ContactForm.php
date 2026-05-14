<?php

namespace App\Filament\Admin\Resources\Audience\Contacts\Schemas;

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
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    TextInput::make('name')->maxLength(255),
                    TextInput::make('phone')->required()->maxLength(255),
                    Toggle::make('is_blocked')->default(false),
                    KeyValue::make('meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
