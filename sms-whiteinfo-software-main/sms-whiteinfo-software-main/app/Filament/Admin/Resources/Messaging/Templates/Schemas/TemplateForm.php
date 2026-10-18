<?php

namespace App\Filament\Admin\Resources\Messaging\Templates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    TextInput::make('name')->required()->maxLength(255),
                    TagsInput::make('variables'),
                    Textarea::make('body')->required()->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
