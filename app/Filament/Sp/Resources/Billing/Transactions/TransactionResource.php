<?php

namespace App\Filament\Sp\Resources\Billing\Transactions;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Resources\Billing\Transactions\Pages\ListTransactions;
use App\Filament\Sp\Resources\Billing\Transactions\Pages\ViewTransaction;
use App\Filament\Sp\Resources\Billing\Transactions\Schemas\TransactionInfolist;
use App\Filament\Sp\Resources\Billing\Transactions\Tables\TransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Billing;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Statements';

    protected static ?string $modelLabel = 'Statement';

    protected static ?string $pluralModelLabel = 'Statements';

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Filament::auth()->id());
    }
}
