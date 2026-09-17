<?php

namespace App\Filament\Admin\Resources\Settings\Gateways;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\CreateGateway;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\EditGateway;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\ListGateways;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\ViewGateway;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\DippingChargesRelationManager;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\OperatorCostPeriodsRelationManager;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\OperatorRatesRelationManager;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\ThirdPartyDippingRelationManager;
use App\Filament\Admin\Resources\Settings\Gateways\Schemas\GatewayForm;
use App\Filament\Admin\Resources\Settings\Gateways\Schemas\GatewayInfolist;
use App\Filament\Admin\Resources\Settings\Gateways\Tables\GatewaysTable;
use App\Models\Gateway;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class GatewayResource extends Resource
{
    protected static ?string $model = Gateway::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Providers;

    protected static ?string $navigationLabel = 'Gateways / Providers';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return GatewayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GatewaysTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GatewayInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('Billing', [
                OperatorRatesRelationManager::class,
                DippingChargesRelationManager::class,
                OperatorCostPeriodsRelationManager::class,
                ThirdPartyDippingRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGateways::route('/'),
            'create' => CreateGateway::route('/create'),
            'view' => ViewGateway::route('/{record}'),
            'edit' => EditGateway::route('/{record}/edit'),
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
