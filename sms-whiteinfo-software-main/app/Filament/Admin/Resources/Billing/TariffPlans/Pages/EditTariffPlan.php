<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Pages;

use App\Filament\Admin\Resources\Billing\TariffPlans\TariffPlanResource;
use App\Models\TariffPlan;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTariffPlan extends EditRecord
{
    protected static string $resource = TariffPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (TariffPlan $record, DeleteAction $action): void {
                    if (! $record->canBeDeleted()) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete this plan')
                            ->body('This plan has active customer assignments. Archive it instead.')
                            ->persistent()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
