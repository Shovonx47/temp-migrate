<?php

namespace App\Filament\Customer\Resources\Messaging\Batches\Pages;

use App\Filament\Customer\Resources\Messaging\Batches\BatchResource;
use Filament\Resources\Pages\ListRecords;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;
}
