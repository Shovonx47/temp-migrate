<?php

namespace App\Filament\Admin\Resources\Finance\Transactions\Pages;

use App\Filament\Admin\Resources\Finance\Transactions\TransactionResource;
use App\Filament\Exports\TransactionExporter;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(TransactionExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
        ];
    }
}
