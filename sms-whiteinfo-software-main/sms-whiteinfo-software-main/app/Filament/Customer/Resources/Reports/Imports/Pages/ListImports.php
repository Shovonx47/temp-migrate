<?php

namespace App\Filament\Customer\Resources\Reports\Imports\Pages;

use App\Filament\Customer\Resources\Reports\Imports\ImportResource;
use Filament\Resources\Pages\ListRecords;

class ListImports extends ListRecords
{
    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
