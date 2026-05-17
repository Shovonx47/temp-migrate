<?php

namespace App\Filament\Customer\Resources\Messaging\DeliveryReports;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Resources\Messaging\DeliveryReports\Pages\ListDeliveryReports;
use App\Filament\Customer\Resources\Messaging\DeliveryReports\Pages\ViewDeliveryReport;
use App\Filament\Customer\Resources\Messaging\DeliveryReports\Schemas\DeliveryReportInfolist;
use App\Filament\Customer\Resources\Messaging\DeliveryReports\Tables\DeliveryReportsTable;
use App\Models\DeliveryReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeliveryReportResource extends Resource
{
    protected static ?string $model = DeliveryReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Messaging;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Delivery Reports';

    protected static ?string $modelLabel = 'Delivery Report';

    protected static bool $shouldRegisterNavigation = false; // TODO:: pause for now

    public static function table(Table $table): Table
    {
        return DeliveryReportsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeliveryReportInfolist::configure($schema);
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
            'index' => ListDeliveryReports::route('/'),
            'view' => ViewDeliveryReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('message', fn ($query) => $query->where('user_id', auth()->id()));
    }
}
