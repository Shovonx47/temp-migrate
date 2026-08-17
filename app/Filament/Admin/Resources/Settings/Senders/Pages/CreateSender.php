<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Pages;

use App\Enums\SenderType;
use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use App\Filament\Imports\SenderImporter;
use App\Models\Gateway;
use App\Models\Sender;
use App\Support\RecipientFileParser;
use Filament\Actions\ImportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateSender extends CreateRecord
{
    protected static string $resource = SenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Bulk Import Senders (CSV/Excel)')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->importer(SenderImporter::class)
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['type'])) {
            $data['type'] = SenderType::normalizeStoredValue($data['type']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $creationMode = $this->form->getRawState()['creation_mode'] ?? 'single';

        if ($creationMode === 'bulk' && ! empty($data['file'])) {
            return $this->handleBulkCreation($data);
        }

        return parent::handleRecordCreation($data);
    }

    private function handleBulkCreation(array $data): Model
    {
        $fileState = $data['file'];
        $filePath = $this->resolveFileAbsolutePath($fileState);

        if (! $filePath || ! file_exists($filePath)) {
            throw new \InvalidArgumentException('Uploaded file could not be found.');
        }

        $parser = app(RecipientFileParser::class);
        $defaultGatewayId = $data['gateway_id'] ?? null;
        $defaultType = SenderType::normalizeStoredValue($data['type'] ?? null) ?? SenderType::AlphaNumeric->value;
        $isEnabled = (bool) ($data['is_enabled'] ?? true);
        $customerIds = $data['users'] ?? [];

        $createdCount = 0;
        $lastSender = null;

        foreach ($parser->stream($filePath) as $row) {
            $senderId = $this->extractSenderId($row);

            if ($senderId === '') {
                continue;
            }

            $gatewayId = $this->extractGatewayId($row) ?? $defaultGatewayId;
            $type = $this->extractSenderType($row, $senderId) ?? $defaultType;

            $sender = Sender::withTrashed()
                ->where('sender_id', $senderId)
                ->when($gatewayId, fn ($q) => $q->where('gateway_id', $gatewayId))
                ->first();

            if ($sender) {
                if ($sender->trashed()) {
                    $sender->restore();
                }
                $sender->update([
                    'type' => $type,
                    'is_enabled' => $isEnabled,
                ]);
            } else {
                $sender = Sender::create([
                    'gateway_id' => $gatewayId,
                    'sender_id' => $senderId,
                    'type' => $type,
                    'is_enabled' => $isEnabled,
                ]);
            }

            if (! empty($customerIds)) {
                $sender->users()->syncWithoutDetaching($customerIds);
            }

            $createdCount++;
            $lastSender = $sender;
        }

        if (! $lastSender) {
            throw new \InvalidArgumentException('No valid sender IDs found in the uploaded file.');
        }

        Notification::make()
            ->title('Senders Bulk Imported')
            ->body("Successfully processed {$createdCount} sender records from the uploaded file.")
            ->icon(Heroicon::CheckCircle)
            ->success()
            ->send();

        return $lastSender;
    }

    private function extractSenderId(array $row): string
    {
        $row = $this->normalizeRowKeys($row);

        $keys = ['sender_id', 'sender', 'cli', 'cli_masking', 'masking', 'sender_name', 'senderid'];

        foreach ($keys as $key) {
            if (! empty($row[$key])) {
                return trim((string) $row[$key]);
            }
        }

        // If no named key, check the first non-empty column value
        foreach ($row as $val) {
            $valStr = trim((string) $val);
            if ($valStr !== '') {
                return $valStr;
            }
        }

        return '';
    }

    private function extractGatewayId(array $row): ?int
    {
        $row = $this->normalizeRowKeys($row);

        $val = $row['gateway'] ?? $row['gateway_id'] ?? null;

        if (! $val) {
            return null;
        }

        $val = trim((string) $val);

        $gateway = Gateway::query()
            ->where('name', $val)
            ->orWhere('id', $val)
            ->first();

        return $gateway?->id;
    }

    private function extractSenderType(array $row, string $senderId): ?string
    {
        $row = $this->normalizeRowKeys($row);

        $val = $row['type'] ?? $row['sender_type'] ?? null;

        if ($val) {
            $normalized = SenderType::normalizeStoredValue(strtolower(trim((string) $val)));

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedKey = str_replace(['/', '-'], '_', $normalizedKey);
            $normalizedKey = preg_replace('/\s+/', '_', $normalizedKey) ?? $normalizedKey;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    private function resolveFileAbsolutePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if ($state instanceof TemporaryUploadedFile) {
            return $state->getRealPath();
        }

        if (is_string($state)) {
            return Storage::disk('local')->exists($state)
                ? Storage::disk('local')->path($state)
                : (file_exists($state) ? $state : null);
        }

        return null;
    }
}
