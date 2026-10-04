<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Pages;

use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use App\Filament\Exports\SenderExporter;
use App\Filament\Imports\SenderImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSenders extends ListRecords
{
    protected static string $resource = SenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Senders')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->importer(SenderImporter::class)
                ->color('gray'),
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
