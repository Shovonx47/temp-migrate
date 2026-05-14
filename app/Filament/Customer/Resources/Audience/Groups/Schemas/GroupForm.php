<?php

namespace App\Filament\Customer\Resources\Audience\Groups\Schemas;

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
                    TextInput::make('name')->required()->maxLength(255),
                ]),
            ]);
    }
}
