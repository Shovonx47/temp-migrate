<?php

namespace App\Filament\Admin\Resources\Finance\Transactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->description('Transaction reference, direction, and amount')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Customer')
                                    ->weight('bold'),
                                TextEntry::make('transactionable_type')
                                    ->label('Reference')
                                    ->badge(),
                                TextEntry::make('direction')
                                    ->label('Direction')
                                    ->badge(),
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('BDT')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color(fn ($record) => $record->direction?->value === 'debit' ? 'danger' : 'success')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Balance')
                    ->description('Balance before and after this transaction')
                    ->icon(Heroicon::OutlinedScale)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('balance_before')
                                    ->label('Balance Before')
                                    ->money('BDT'),
                                TextEntry::make('balance_after')
                                    ->label('Balance After')
                                    ->money('BDT'),
                            ]),
                    ]),

                Section::make('Details')
                    ->description('Additional transaction information')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
