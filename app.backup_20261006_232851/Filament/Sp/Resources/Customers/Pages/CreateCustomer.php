<?php

namespace App\Filament\Sp\Resources\Customers\Pages;

use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\UserType;
use App\Filament\Sp\Resources\Customers\CustomerResource;
use App\Models\TariffPlanAssignment;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['type'] = UserType::Customer;
        $data['sp_id'] = ($user?->type?->isSp() && $user?->sp_id) ? $user->sp_id : $user?->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $selectedPlanId = $this->data['tariff_plan_id'] ?? null;

        if ($selectedPlanId) {
            TariffPlanAssignment::create([
                'user_id' => $this->record->id,
                'tariff_plan_id' => $selectedPlanId,
                'assigned_by' => Filament::auth()->id(),
                'effective_from' => now(),
                'status' => TariffPlanAssignmentStatus::Active->value,
                'notes' => 'Assigned via S P Customer Create',
            ]);
        }
    }
}
