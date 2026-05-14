<?php

namespace App\Filament\Admin\Resources\Billing\Deposits;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Billing\Deposits\Pages\EditDeposit;
use App\Filament\Admin\Resources\Billing\Deposits\Pages\ListDeposits;
use App\Filament\Admin\Resources\Billing\Deposits\Pages\ViewDeposit;
use App\Filament\Admin\Resources\Billing\Deposits\Schemas\DepositForm;
use App\Filament\Admin\Resources\Billing\Deposits\Schemas\DepositInfolist;
use App\Filament\Admin\Resources\Billing\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Deposits';

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
            'edit' => EditDeposit::route('/{record}/edit'),
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
