<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\AssignedCustomers;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\CreateTariffPlan;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\EditTariffPlan;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\ListTariffPlans;
use App\Filament\Admin\Resources\Billing\TariffPlans\Pages\ViewTariffPlan;
use App\Filament\Admin\Resources\Billing\TariffPlans\RelationManagers\TariffRatesRelationManager;
use App\Filament\Admin\Resources\Billing\TariffPlans\Schemas\TariffPlanForm;
use App\Filament\Admin\Resources\Billing\TariffPlans\Schemas\TariffPlanInfolist;
use App\Filament\Admin\Resources\Billing\TariffPlans\Tables\TariffPlansTable;
use App\Models\TariffPlan;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TariffPlanResource extends Resource
{
    protected static ?string $model = TariffPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Tariff Plans';

    protected static ?string $modelLabel = 'Tariff Plan';

    protected static ?string $pluralModelLabel = 'Tariff Plans';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TariffPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TariffPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TariffPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TariffRatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTariffPlans::route('/'),
            'create' => CreateTariffPlan::route('/create'),
            'assigned-customers' => AssignedCustomers::route('/{record}/customers'),
            'view' => ViewTariffPlan::route('/{record}'),
            'edit' => EditTariffPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('sp_id');
    }

    public static function canViewAny(): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'billing.view');
    }

    public static function canCreate(): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'billing.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'billing.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return app(SpPermissionService::class)->can(auth('superman')->user(), 'billing.manage');
    }
}
