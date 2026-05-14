<?php

namespace App\Filament\Customer\Resources\Messaging\Templates\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Details')->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Textarea::make('body')->required()->rows(6)->columnSpanFull(),
                    KeyValue::make('variables')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
