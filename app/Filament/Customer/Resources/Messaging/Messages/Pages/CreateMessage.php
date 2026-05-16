<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Jobs\ProcessMessageBatchJob;
use App\Models\MessageBatch;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $source = $data['source'] ?? 'manual';
        $body = $data['body'] ?? '';
        $bodyVariables = $data['body_variables'] ?? [];

        $batch = DB::transaction(function () use ($source, $body, $data) {
            $batch = MessageBatch::create([
                'user_id' => Auth::id(),
                'sender_id' => $data['sender_id'] ?? null,
                'body' => $body,
                'source_type' => $source,
                'source_config' => [],
            ]);

            $batch->update([
                'source_config' => $this->buildSourceConfig($source, $data, $batch),
            ]);

            return $batch;
        });

        ProcessMessageBatchJob::dispatch(
            $batch,
            $source,
            $batch->source_config,
            $body,
            $bodyVariables,
        )->afterCommit();

        Notification::make()
            ->title('Messages are being processed')
            ->body('We will notify you when the recipients are ready to send.')
            ->icon(Heroicon::Inbox)
            ->info()
            ->send();

        return $batch;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return MessageResource::getUrl('index');
    }

    private function buildSourceConfig(string $source, array $data, MessageBatch $batch): array
    {
        return match ($source) {
            'manual' => ['recipients' => $data['recipients'] ?? []],
            'upload' => [
                'disk' => 'local',
                'file_path' => $this->relocateUploadedFile($data['file'] ?? null, $batch),
                'mapping' => $data['mapping'] ?? [],
            ],
            'group' => ['group_ids' => $data['groups'] ?? []],
            default => [],
        };
    }

    private function relocateUploadedFile(mixed $state, MessageBatch $batch): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if (! is_string($state) || $state === '' || ! Storage::disk('local')->exists($state)) {
            return null;
        }

        $extension = strtolower(pathinfo($state, PATHINFO_EXTENSION)) ?: 'csv';
        $destination = 'message-batches/'.$batch->id.'/source.'.$extension;

        Storage::disk('local')->move($state, $destination);

        return $destination;
    }
}
