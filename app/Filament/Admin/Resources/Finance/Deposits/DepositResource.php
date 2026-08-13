<?php

namespace App\Filament\Admin\Resources\Finance\Deposits;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Finance\Deposits\Pages\ListDeposits;
use App\Filament\Admin\Resources\Finance\Deposits\Pages\ViewDeposit;
use App\Filament\Admin\Resources\Finance\Deposits\Schemas\DepositForm;
use App\Filament\Admin\Resources\Finance\Deposits\Schemas\DepositInfolist;
use App\Filament\Admin\Resources\Finance\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Deposits';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return DepositForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DepositInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
