<?php

namespace App\Filament\Sp\Resources\Billing\Deposits;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Resources\Billing\Deposits\Pages\ListDeposits;
use App\Filament\Sp\Resources\Billing\Deposits\Pages\ViewDeposit;
use App\Filament\Sp\Resources\Billing\Deposits\Schemas\DepositInfolist;
use App\Filament\Sp\Resources\Billing\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Billing;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Deposits';

    protected static ?string $recordTitleAttribute = 'transaction_id';

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

    public static function canViewAny(): bool
    {
        return app(SpPermissionService::class)->can(auth('sp')->user(), 'billing.view');
    }
}
