<?php

namespace App\Filament\Customer\Resources\Reports\Exports\Pages;

use App\Filament\Customer\Resources\Reports\Exports\ExportResource;
use Filament\Resources\Pages\ListRecords;

class ListExports extends ListRecords
{
    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
