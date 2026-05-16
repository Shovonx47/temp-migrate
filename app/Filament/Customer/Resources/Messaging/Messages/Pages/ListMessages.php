<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createQuick')
                ->label('Quick Message')
                ->icon(Heroicon::OutlinedBolt)
                ->color('gray')
                ->url(fn (): string => MessageResource::getUrl('create-quick')),
            CreateAction::make()
                ->label('Smart Message')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->url(MessageResource::getUrl('create-smart')),
        ];
    }
}
