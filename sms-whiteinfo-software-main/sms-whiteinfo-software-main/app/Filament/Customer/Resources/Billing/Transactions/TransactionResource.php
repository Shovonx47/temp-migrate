<?php

namespace App\Filament\Customer\Resources\Billing\Transactions;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Billing\Transactions\Pages\ListTransactions;
use App\Filament\Customer\Resources\Billing\Transactions\Pages\ViewTransaction;
use App\Filament\Customer\Resources\Billing\Transactions\Schemas\TransactionInfolist;
use App\Filament\Customer\Resources\Billing\Transactions\Tables\TransactionsTable;
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

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Transactional Report';

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
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
