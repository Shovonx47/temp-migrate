<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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

        $campaign = Campaign::create([
            'user_id' => Auth::id(),
            'sender_id' => $data['sender_id'] ?? null,
            'name' => $this->generateCampaignName($body),
            'body' => $body,
            'status' => CampaignStatus::Draft,
        ]);

        $config = match ($source) {
            'manual' => ['recipients' => $data['recipients'] ?? []],
            'upload' => [
                'disk' => 'local',
                'file_path' => $this->relocateUploadedFile($data['file'] ?? null, $campaign),
                'mapping' => $data['mapping'] ?? [],
            ],
            'group' => ['group_ids' => $data['groups'] ?? []],
            default => [],
        };

        ProcessCampaignJob::dispatch($campaign, $source, $config, $body, $bodyVariables)
            ->afterCommit();

        Notification::make()
            ->title('Campaign is being processed')
            ->body('We will notify you when the recipients are ready to send.')
            ->icon(Heroicon::Inbox)
            ->info()
            ->send();

        return $campaign;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return MessageResource::getUrl('index');
    }

    private function relocateUploadedFile(mixed $state, Campaign $campaign): ?string
    {
        // FileUpload (single, ->storeFiles(true)) returns a disk-relative path after upload.
        // Older Livewire/Filament shapes can hand back an array — handle that defensively.
        if (is_array($state)) {
            $state = reset($state);
        }

        if (! is_string($state) || $state === '' || ! Storage::disk('local')->exists($state)) {
            return null;
        }

        $extension = strtolower(pathinfo($state, PATHINFO_EXTENSION)) ?: 'csv';
        $destination = 'campaigns/'.$campaign->id.'/source.'.$extension;

        Storage::disk('local')->move($state, $destination);

        return $destination;
    }

    private function generateCampaignName(string $body): string
    {
        $snippet = trim(mb_substr($body, 0, 40));

        return ($snippet === '' ? 'Untitled campaign' : $snippet).' · '.now()->format('Y-m-d H:i');
    }
}
