<?php

namespace App\Filament\Sp\Resources\Messaging\Senders\Pages;

use App\Enums\SenderApprovalStatus;
use App\Enums\SenderType;
use App\Filament\Sp\Resources\Messaging\Senders\SenderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSender extends CreateRecord
{
    protected static string $resource = SenderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['type'])) {
            $data['type'] = SenderType::normalizeStoredValue($data['type']);
        }

        $data['gateway_id'] = null;
        $data['is_enabled'] = false;
        $data['approval_status'] = SenderApprovalStatus::Pending->value;
        $data['requested_by'] = auth()->id();
        $data['reviewed_by'] = null;
        $data['reviewed_at'] = null;
        $data['rejection_reason'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Model $sender */
        $sender = $this->record;

        $sender->users()->syncWithoutDetaching([auth()->id()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sender ID submitted for approval';
    }
}
