<?php

namespace App\Filament\Admin\Resources\Messaging\Campaigns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignInfolist
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
                                TextEntry::make('name')->label('Campaign Name'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('scheduled_at')->dateTime(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
