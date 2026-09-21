<?php

namespace App\Filament\Customer\Resources\Messaging\Templates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')->label('Template Name')->weight('bold'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('body')->columnSpanFull(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
