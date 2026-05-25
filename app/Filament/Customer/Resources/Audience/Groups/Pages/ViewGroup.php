<?php

namespace App\Filament\Customer\Resources\Audience\Groups\Pages;

use App\Filament\Customer\Resources\Audience\Groups\GroupResource;
use App\Filament\Imports\ContactImporter;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGroup extends ViewRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import Contacts')
                ->icon(Heroicon::OutlinedDocumentArrowUp)
                ->importer(ContactImporter::class)
                ->options(['group_id' => $this->getRecord()->getKey()]),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
