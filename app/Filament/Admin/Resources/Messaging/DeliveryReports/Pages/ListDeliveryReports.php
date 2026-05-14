<?php

namespace App\Filament\Admin\Resources\Messaging\DeliveryReports\Pages;

use App\Filament\Admin\Resources\Messaging\DeliveryReports\DeliveryReportResource;
use App\Filament\Exports\DeliveryReportExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDeliveryReports extends ListRecords
{
    protected static string $resource = DeliveryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(DeliveryReportExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }
}
