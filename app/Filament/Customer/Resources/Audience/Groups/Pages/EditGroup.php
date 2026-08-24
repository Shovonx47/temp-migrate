<?php

namespace App\Filament\Customer\Resources\Audience\Groups\Pages;

use App\Filament\Customer\Resources\Audience\Groups\GroupResource;
use App\Filament\Imports\ContactImporter;
use Filament\Actions\DeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditGroup extends EditRecord
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
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
