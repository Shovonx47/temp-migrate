<?php

namespace App\Filament\Admin\Resources\Contacts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')->label('User')->weight('bold'),
                                TextEntry::make('group.name')->label('Group'),
                                TextEntry::make('name')->label('Contact Name'),
                                TextEntry::make('phone'),
                                TextEntry::make('email'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
