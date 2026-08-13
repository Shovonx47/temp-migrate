<?php

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Enums\TariffPlanAssignmentStatus;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\TariffPlanAssignment;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tariff_plan_id'] = $this->record->activeTariffAssignment?->tariff_plan_id;

        return $data;
    }

    protected function afterSave(): void
    {
        $selectedPlanId = $this->data['tariff_plan_id'] ?? null;
        $currentAssignment = $this->record->activeTariffAssignment;
        $currentPlanId = $currentAssignment?->tariff_plan_id;

        if ($selectedPlanId && (int) $selectedPlanId !== (int) $currentPlanId) {
            TariffPlanAssignment::where('user_id', $this->record->id)
                ->where('status', TariffPlanAssignmentStatus::Active->value)
                ->update(['status' => TariffPlanAssignmentStatus::Superseded->value]);

            TariffPlanAssignment::create([
                'user_id' => $this->record->id,
                'tariff_plan_id' => $selectedPlanId,
                'assigned_by' => auth()->id(),
                'effective_from' => now(),
                'status' => TariffPlanAssignmentStatus::Active->value,
                'notes' => 'Assigned via Customer Edit',
            ]);
        } elseif (is_null($selectedPlanId) && $currentAssignment) {
            TariffPlanAssignment::where('user_id', $this->record->id)
                ->where('status', TariffPlanAssignmentStatus::Active->value)
                ->update(['status' => TariffPlanAssignmentStatus::Superseded->value]);
        }
    }
}
