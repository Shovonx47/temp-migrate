<?php

namespace App\Filament\Admin\Resources\Senders\Pages;

use App\Filament\Admin\Resources\Senders\SenderResource;
use App\Filament\Exports\SenderExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSenders extends ListRecords
{
    protected static string $resource = SenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(SenderExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }
}
