<?php

namespace App\Filament\Admin\Resources\Groups\Pages;

use App\Filament\Admin\Resources\Groups\GroupResource;
use App\Filament\Exports\GroupExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(GroupExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }
}
