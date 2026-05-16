<?php

namespace App\Filament\Admin\Resources\Countries\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')->weight('bold'),
                            TextEntry::make('code')->badge(),
                            TextEntry::make('iso_alpha2')->label('ISO Alpha-2'),
                            IconEntry::make('is_active')->boolean(),
                        ]),
                    ]),
            ]);
    }
}
