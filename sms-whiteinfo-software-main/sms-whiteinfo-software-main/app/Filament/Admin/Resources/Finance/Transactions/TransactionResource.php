<?php

namespace App\Filament\Admin\Resources\Finance\Transactions;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Finance\Transactions\Pages\ListTransactions;
use App\Filament\Admin\Resources\Finance\Transactions\Pages\ViewTransaction;
use App\Filament\Admin\Resources\Finance\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Finance\Transactions\Schemas\TransactionInfolist;
use App\Filament\Admin\Resources\Finance\Transactions\Tables\TransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
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
