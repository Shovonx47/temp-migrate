<?php

namespace App\Filament\Admin\Resources\Users\Sps\Pages;

use App\Filament\Admin\Resources\Users\Sps\Actions\DepositAction;
use App\Filament\Admin\Resources\Users\Sps\SpResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSp extends ViewRecord
{
    protected static string $resource = SpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DepositAction::make(),
            EditAction::make()
                ->label('Edit S P'),
        ];
    }
}
