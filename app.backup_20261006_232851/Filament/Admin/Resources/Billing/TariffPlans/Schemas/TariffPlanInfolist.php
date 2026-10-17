<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TariffPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->weight('bold'),
                                TextEntry::make('type')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('currency'),
                                TextEntry::make('createdBy.name')
                                    ->label('Created By')
                                    ->placeholder('System'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                            ]),
                        TextEntry::make('description')
                            ->placeholder('No description provided.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Assignment Summary')
                    ->schema([
                        TextEntry::make('activeCustomerAssignments')
                            ->label('Active Customers')
                            ->state(fn (object $record): string => (string) $record->activeCustomerAssignments()->count()),
                    ]),
            ]);
    }
}
