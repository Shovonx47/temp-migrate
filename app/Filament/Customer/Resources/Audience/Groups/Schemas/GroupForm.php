<?php

namespace App\Filament\Customer\Resources\Audience\Groups\Schemas;

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
                Section::make('Group Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Group Name')
                            ->placeholder('Enter group name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter group description')
                            ->maxLength(500),
                    ]),
            ]);
    }
}
