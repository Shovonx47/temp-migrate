<?php

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\TariffPlanAssignment;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = UserType::Customer;
        $data['created_by_admin'] = true;
        $data['created_by'] = auth('superman')->id() ?? auth()->id();
        $data['status'] = $data['status'] ?? UserStatus::Approved;
        $data['email_verified_at'] = now();

        if (! empty($data['phone'])) {
            $data['phone_verified_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $selectedPlanId = $this->data['tariff_plan_id'] ?? null;

        if ($selectedPlanId) {
            TariffPlanAssignment::create([
                'user_id' => $this->record->id,
                'tariff_plan_id' => $selectedPlanId,
                'assigned_by' => auth()->id(),
                'effective_from' => now(),
                'status' => TariffPlanAssignmentStatus::Active->value,
                'notes' => 'Assigned via Customer Create',
            ]);
        }
    }
}
