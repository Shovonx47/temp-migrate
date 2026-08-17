<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Pages;

use App\Filament\Admin\Resources\Settings\Senders\Actions\ApproveSenderAction;
use App\Filament\Admin\Resources\Settings\Senders\Actions\RejectSenderAction;
use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSender extends ViewRecord
{
    protected static string $resource = SenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ApproveSenderAction::make(),
            RejectSenderAction::make(),
            EditAction::make(),
        ];
    }
}
