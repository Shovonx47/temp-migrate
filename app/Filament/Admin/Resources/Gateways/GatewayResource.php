<?php

namespace App\Filament\Admin\Resources\Gateways;

use App\Filament\Admin\Resources\Gateways\Pages\CreateGateway;
use App\Filament\Admin\Resources\Gateways\Pages\EditGateway;
use App\Filament\Admin\Resources\Gateways\Pages\ListGateways;
use App\Filament\Admin\Resources\Gateways\Pages\ViewGateway;
use App\Filament\Admin\Resources\Gateways\Schemas\GatewayForm;
use App\Filament\Admin\Resources\Gateways\Schemas\GatewayInfolist;
use App\Filament\Admin\Resources\Gateways\Tables\GatewaysTable;
use App\Models\Gateway;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GatewayResource extends Resource
{
    protected static ?string $model = Gateway::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static string|\UnitEnum|null $navigationGroup = 'System Settings';

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
            //
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
