<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Filament\Customer\Resources\Messaging\Batches\BatchResource;
use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use App\Models\Contact;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Throwable;

class CreateSmartMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $navigationLabel = 'Smart Message';

    protected static ?string $title = 'Smart Message';

    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @throws Halt
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        $source = $data['source'] ?? 'manual';
        $body = $data['body'] ?? '';
        $bodyVariables = $data['body_variables'] ?? [];

        $this->assertAffordable($source, $body, $data);

        /** @var User $user */
        $user = Auth::user();
        $requiresApproval = $user->requiresSmsApproval();

        $batch = Batch::create([
            'user_id' => $user->id,
            'sender_id' => $data['sender_id'] ?? null,
            'body' => $body,
            'source_type' => $source,
            'source_config' => [],
            'status' => $requiresApproval ? BatchStatus::Pending : BatchStatus::Processing,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]);

        $batch->update([
            'source_config' => $this->buildSourceConfig($source, $data, $batch),
        ]);

        if (! $requiresApproval) {
            ProcessBatchJob::dispatch(
                $batch,
                $source,
                $batch->source_config,
                $body,
                $bodyVariables,
            )->onQueue('batches')->afterCommit();
        }

        Notification::make()
            ->title($requiresApproval ? 'Awaiting approval' : 'Messages are being processed')
            ->body($requiresApproval
                ? 'Your message batch is pending admin approval before sending.'
                : 'We will notify you when the recipients are ready to send.'
            )
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
        return BatchResource::getUrl('view', ['record' => $this->record]);
    }

    private function buildSourceConfig(string $source, array $data, Batch $batch): array
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

    /**
     * Block submission when the user's balance can't cover the estimated batch cost.
     * Uses the highest configured operator rate for the chosen sender type as a
     * conservative upper bound — the job will compute exact per-recipient costs.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Halt
     */
    private function assertAffordable(string $source, string $body, array $data): void
    {
        $user = Auth::user();
        $recipientCount = $this->estimateRecipientCount($source, $data);

        if ($recipientCount === 0) {
            return;
        }

        $maxRate = $this->resolveMaxRate($user, (int) ($data['sender_id'] ?? 0));

        if ($maxRate === null) {
            return; // no rates configured anywhere — job will fail with a precise error
        }

        $parts = MessageMetrics::for($body)->parts;
        $estimated = round($maxRate * $parts * $recipientCount, 4);

        if ($user->balance >= $estimated) {
            return;
        }

        $shortfall = (float) max($estimated - (float) $user->balance, 0);

        Notification::make()
            ->title('Insufficient balance')
            ->body(sprintf(
                'This batch needs up to %s. Top up %s to send.',
                Number::currency($estimated, 'BDT'),
                Number::currency($shortfall, 'BDT'),
            ))
            ->icon(Heroicon::Banknotes)
            ->danger()
            ->send();

        $this->halt();
    }

    private function resolveMaxRate(User $user, int $senderId): ?float
    {
        $sender = $senderId > 0 ? Sender::find($senderId) : null;

        if (! $sender || $sender->type === null) {
            return null;
        }

        $resolver = OperatorRateResolver::forUser($user);
        $rates = $resolver->ratesForUser($sender->type->isMasking());

        return $rates === [] ? null : max($rates);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function estimateRecipientCount(string $source, array $data): int
    {
        return match ($source) {
            'manual' => count($data['recipients'] ?? []),
            'group' => $this->countContactsInGroups($data['groups'] ?? []),
            'upload' => (int) ($data['file_total_rows'] ?? 0),
            default => 0,
        };
    }

    /**
     * @param  array<int, int|string>  $groupIds
     */
    private function countContactsInGroups(array $groupIds): int
    {
        if ($groupIds === []) {
            return 0;
        }

        return Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->count();
    }

    private function relocateUploadedFile(mixed $state, Batch $batch): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if (! is_string($state) || $state === '' || ! Storage::disk('local')->exists($state)) {
            return null;
        }

        $extension = strtolower(pathinfo($state, PATHINFO_EXTENSION)) ?: 'csv';
        $destination = 'batches/'.$batch->id.'/source.'.$extension;

        Storage::disk('local')->move($state, $destination);

        return $destination;
    }
}
