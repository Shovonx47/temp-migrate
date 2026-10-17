<?php

namespace App\Filament\Admin\Resources\Users\Sps\Pages;

use App\Filament\Admin\Resources\Users\Sps\SpResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSps extends ListRecords
{
    protected static string $resource = SpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New S P'),
        ];
    }
}
