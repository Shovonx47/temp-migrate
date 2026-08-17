<?php

namespace App\Filament\Admin\Resources\Users\Sps;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Users\Sps\Pages\CreateSp;
use App\Filament\Admin\Resources\Users\Sps\Pages\EditSp;
use App\Filament\Admin\Resources\Users\Sps\Pages\ListSps;
use App\Filament\Admin\Resources\Users\Sps\Pages\ViewSp;
use App\Filament\Admin\Resources\Users\Sps\RelationManagers\CustomersRelationManager;
use App\Filament\Admin\Resources\Users\Sps\RelationManagers\DistributionsRelationManager;
use App\Filament\Admin\Resources\Users\Sps\RelationManagers\EmployeesRelationManager;
use App\Filament\Admin\Resources\Users\Sps\RelationManagers\SendersRelationManager;
use App\Filament\Admin\Resources\Users\Sps\Schemas\SpForm;
use App\Filament\Admin\Resources\Users\Sps\Schemas\SpInfolist;
use App\Filament\Admin\Resources\Users\Sps\Tables\SpsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SpResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'S P List';

    protected static ?string $modelLabel = 'S P';

    protected static ?string $pluralModelLabel = 'S Ps';

    protected static ?string $slug = 'sps';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return SpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SpInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            CustomersRelationManager::class,
            EmployeesRelationManager::class,
            DistributionsRelationManager::class,
            SendersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSps::route('/'),
            'create' => CreateSp::route('/create'),
            'view' => ViewSp::route('/{record}'),
            'edit' => EditSp::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->scopes(['sps'])
            ->whereNull('sp_id');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
