<?php

namespace App\Filament\Customer\Resources\Billing\Deposits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DepositInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment')
                    ->description('Payment details and status')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('BDT')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary'),
                                TextEntry::make('payment_method')
                                    ->label('Method')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                                TextEntry::make('paid_at')
                                    ->label('Paid At')
                                    ->dateTime()
                                    ->placeholder('Not paid yet'),
                            ]),
                    ]),
            ]);
    }
}
