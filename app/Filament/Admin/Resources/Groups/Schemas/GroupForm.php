<?php

namespace App\Filament\Admin\Resources\Groups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Group Details')->schema([
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    TextInput::make('name')->required()->maxLength(255),
                    Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
