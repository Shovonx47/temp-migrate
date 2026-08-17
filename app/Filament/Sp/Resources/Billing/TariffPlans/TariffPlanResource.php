<?php

namespace App\Filament\Sp\Resources\Billing\TariffPlans;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Resources\Billing\TariffPlans\Pages\CreateTariffPlan;
use App\Filament\Sp\Resources\Billing\TariffPlans\Pages\EditTariffPlan;
use App\Filament\Sp\Resources\Billing\TariffPlans\Pages\ListTariffPlans;
use App\Filament\Sp\Resources\Billing\TariffPlans\Pages\ViewTariffPlan;
use App\Filament\Sp\Resources\Billing\TariffPlans\RelationManagers\TariffRatesRelationManager;
use App\Filament\Sp\Resources\Billing\TariffPlans\Schemas\TariffPlanForm;
use App\Filament\Sp\Resources\Billing\TariffPlans\Schemas\TariffPlanInfolist;
use App\Filament\Sp\Resources\Billing\TariffPlans\Tables\TariffPlansTable;
use App\Models\TariffPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TariffPlanResource extends Resource
{
    protected static ?string $model = TariffPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Tariff Plans';

    protected static ?string $modelLabel = 'Tariff Plan';

    protected static ?string $pluralModelLabel = 'Tariff Plans';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

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
            'view' => ViewTariffPlan::route('/{record}'),
            'edit' => EditTariffPlan::route('/{record}/edit'),
        ];
    }
}
