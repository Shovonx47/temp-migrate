<?php

namespace App\Filament\Admin\Resources\Messages\Pages;

use App\Filament\Admin\Resources\Messages\MessageResource;
use App\Filament\Exports\MessageExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(MessageExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }
}
