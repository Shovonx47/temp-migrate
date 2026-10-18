<?php

namespace App\Filament\Admin\Resources\Finance\Deposits\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
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
                Section::make('Customer')
                    ->description('Customer who made this deposit')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Name')
                                    ->weight('bold'),
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->copyable(),
                                TextEntry::make('user.phone')
                                    ->label('Phone'),
                            ]),
                    ]),

                Section::make('Payment')
                    ->description('Payment details and status')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('BDT')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary'),
                                TextEntry::make('currency')
                                    ->label('Currency'),
                                TextEntry::make('payment_method')
                                    ->label('Method')
                                    ->badge(),
                                TextEntry::make('transaction_id')
                                    ->label('PipaPay ID')
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                                TextEntry::make('paid_at')
                                    ->label('Paid At')
                                    ->dateTime()
                                    ->placeholder('Not paid yet'),
                            ]),
                    ]),

                Section::make('Gateway Response')
                    ->description('Raw payload from PipaPay')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        KeyValueEntry::make('meta')
                            ->label('Response Data'),
                    ]),
            ]);
    }
}
