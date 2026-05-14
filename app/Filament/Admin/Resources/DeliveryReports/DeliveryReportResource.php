<?php

namespace App\Filament\Admin\Resources\DeliveryReports;

use App\Filament\Admin\Resources\DeliveryReports\Pages\CreateDeliveryReport;
use App\Filament\Admin\Resources\DeliveryReports\Pages\EditDeliveryReport;
use App\Filament\Admin\Resources\DeliveryReports\Pages\ListDeliveryReports;
use App\Filament\Admin\Resources\DeliveryReports\Pages\ViewDeliveryReport;
use App\Filament\Admin\Resources\DeliveryReports\Schemas\DeliveryReportForm;
use App\Filament\Admin\Resources\DeliveryReports\Schemas\DeliveryReportInfolist;
use App\Filament\Admin\Resources\DeliveryReports\Tables\DeliveryReportsTable;
use App\Models\DeliveryReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliveryReportResource extends Resource
{
    protected static ?string $model = DeliveryReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Messaging';

    public static function form(Schema $schema): Schema
    {
        return DeliveryReportForm::configure($schema);
    }

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
            'create' => CreateDeliveryReport::route('/create'),
            'view' => ViewDeliveryReport::route('/{record}'),
            'edit' => EditDeliveryReport::route('/{record}/edit'),
        ];
    }
}
