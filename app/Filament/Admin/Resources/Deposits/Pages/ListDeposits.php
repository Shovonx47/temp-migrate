<?php

namespace App\Filament\Admin\Resources\Deposits\Pages;

use App\Filament\Admin\Resources\Deposits\DepositResource;
use App\Filament\Exports\DepositExporter;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(DepositExporter::class)
                ->columnMappingColumns(3),
        ];
    }
}
