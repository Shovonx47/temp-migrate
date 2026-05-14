<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Exports\UserExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(UserExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }
}
