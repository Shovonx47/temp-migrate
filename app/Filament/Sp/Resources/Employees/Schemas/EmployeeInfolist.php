<?php

namespace App\Filament\Sp\Resources\Employees\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->description('Employee profile and access credentials')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->weight('bold'),
                                TextEntry::make('email')
                                    ->label('Email')
                                    ->copyable()
                                    ->icon(Heroicon::OutlinedClipboard),
                                TextEntry::make('phone')
                                    ->label('Phone'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                            ]),
                    ]),
                Section::make('Access')
                    ->description('Panel access details')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        TextEntry::make('type')
                            ->label('Account Type')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Joined At')
                            ->dateTime()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
