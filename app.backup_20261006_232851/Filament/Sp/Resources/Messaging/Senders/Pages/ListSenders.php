<?php

namespace App\Filament\Sp\Resources\Messaging\Senders\Pages;

use App\Filament\Sp\Resources\Messaging\Senders\SenderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSenders extends ListRecords
{
    protected static string $resource = SenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Request Sender ID'),
        ];
    }
}
