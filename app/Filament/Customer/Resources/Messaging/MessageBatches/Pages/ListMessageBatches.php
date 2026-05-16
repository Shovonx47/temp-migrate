<?php

namespace App\Filament\Customer\Resources\Messaging\MessageBatches\Pages;

use App\Filament\Customer\Resources\Messaging\MessageBatches\MessageBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListMessageBatches extends ListRecords
{
    protected static string $resource = MessageBatchResource::class;
}
