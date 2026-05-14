<?php

namespace App\Filament\Admin\Resources\Templates\Pages;

use App\Filament\Admin\Resources\Templates\TemplateResource;
use App\Filament\Exports\TemplateExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(TemplateExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }
}
