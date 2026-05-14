<?php

namespace App\Filament\Customer\Resources\Billing\Deposits;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Billing\Deposits\Pages\ListDeposits;
use App\Filament\Customer\Resources\Billing\Deposits\Pages\ViewDeposit;
use App\Filament\Customer\Resources\Billing\Deposits\Schemas\DepositInfolist;
use App\Filament\Customer\Resources\Billing\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Deposits';

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DepositInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
            'view' => ViewDeposit::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
