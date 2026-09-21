<?php

namespace App\Filament\Admin\Resources\Messaging\Batches\Pages;

use App\Filament\Admin\Resources\Messaging\Batches\BatchResource;
use Filament\Resources\Pages\ListRecords;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;
}
