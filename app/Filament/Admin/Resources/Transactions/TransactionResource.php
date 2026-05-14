<?php

namespace App\Filament\Admin\Resources\Transactions;

use App\Filament\Admin\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Admin\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Admin\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Transactions\Tables\TransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|null|\UnitEnum $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Transactions';

    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
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

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }
}
