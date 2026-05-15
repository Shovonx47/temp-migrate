<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Send Message')
                ->icon(Heroicon::OutlinedPaperAirplane)
        ];
    }
}
