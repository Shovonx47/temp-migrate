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
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Throwable;

class CreateSmartMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $navigationLabel = 'Bulk Message';

    protected static ?string $title = 'Bulk Message';

    protected ?bool $hasDatabaseTransactions = true;

    public function hasFormWrapper(): bool
    {
        return false;
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Send Message')
            ->icon(Heroicon::PaperAirplane)
            ->requiresConfirmation()
            ->modalHeading('Double-Check & Confirm SMS')
            ->modalDescription('Please verify your message content and review the cost breakdown below before sending.')
            ->modalIcon(Heroicon::ExclamationTriangle)
            ->modalIconColor('warning')
            ->modalSubmitActionLabel('Yes, Send Message')
            ->modalCancelActionLabel('No, let me double-check')
            ->modalContent(function (): HtmlString {
                $data = $this->form->getRawState();
                $source = $data['source'] ?? 'manual';
                $body = (string) ($data['body'] ?? '');

                /** @var User|null $user */
                $user = Auth::user();
                $senderId = (int) ($data['sender_id'] ?? 0);
                $sender = $senderId > 0 ? Sender::find($senderId) : null;

                $recipientCount = $this->estimateRecipientCount($source, $data);
                $parts = $body !== '' ? MessageMetrics::for($body)->parts : 1;

                $isMasking = $sender?->type?->isMasking() ?? false;
                $resolver = $user ? OperatorRateResolver::forUser($user) : null;
                $rates = $resolver ? $resolver->ratesForUser($isMasking) : [];
                $maxRate = ! empty($rates) ? max($rates) : 0.0;

                $estimatedCost = round($maxRate * $parts * $recipientCount, 4);
                $previousBalance = (float) ($user?->balance ?? 0);
                $balanceAfter = round(max(0, $previousBalance - $estimatedCost), 4);

                $costFormatted = Number::currency($estimatedCost, 'BDT');
                $prevFormatted = Number::currency($previousBalance, 'BDT');
                $afterFormatted = Number::currency($balanceAfter, 'BDT');
                $rateFormatted = Number::currency($maxRate, 'BDT');
                $escapedBody = e($body);
                $senderName = e($sender?->name ?? 'Default / Not Selected');

                return new HtmlString(<<<HTML
<div class="space-y-4 text-sm text-left">
    <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/50 p-3.5 text-amber-800 dark:text-amber-200">
        <div class="flex items-start gap-2.5">
            <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-semibold text-sm">Is your message content ready?</p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                    Double-check your SMS text, Sender ID (<strong>{$senderName}</strong>), and recipients. If you want to make any edits, click <strong>"No, let me double-check"</strong>.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Message Preview</p>
        <p class="text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono bg-white dark:bg-gray-950 p-2.5 rounded border border-gray-200 dark:border-gray-800 max-h-24 overflow-y-auto">{$escapedBody}</p>
    </div>

    <div class="grid grid-cols-3 gap-2">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2.5 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">Recipients</p>
            <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-gray-100">{$recipientCount}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2.5 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">SMS Parts</p>
            <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-gray-100">{$parts}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2.5 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">Rate / Part</p>
            <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-gray-100">{$rateFormatted}</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700 overflow-hidden text-xs">
        <div class="flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-gray-950">
            <span class="text-gray-600 dark:text-gray-400">Previous balance:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">{$prevFormatted}</span>
        </div>
        <div class="flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-gray-950">
            <span class="text-gray-600 dark:text-gray-400">Total SMS cost ({$recipientCount} × {$parts} × {$rateFormatted}):</span>
            <span class="font-bold text-red-600 dark:text-red-400">− {$costFormatted}</span>
        </div>
        <div class="flex items-center justify-between px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 font-semibold">
            <span class="text-gray-900 dark:text-gray-100">Balance after sending:</span>
            <span class="text-emerald-700 dark:text-emerald-400 text-sm">{$afterFormatted}</span>
        </div>
    </div>
</div>
HTML);
            })
            ->action('create');
    }

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
            'manual' => ['recipients' => $this->resolveManualRecipients($data)],
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
     * @param  array<string, mixed>  $data
     * @return array<int, array{phone: string, values: array<string, string>}>
     */
    private function resolveManualRecipients(array $data): array
    {
        $recipients = [];

        // If 'phones' (TagsInput) was used
        if (! empty($data['phones'])) {
            $phones = is_array($data['phones'])
                ? $data['phones']
                : explode(',', (string) $data['phones']);

            foreach ($phones as $phone) {
                $subPhones = preg_split('/[,\s]+/', trim((string) $phone));
                foreach ($subPhones as $p) {
                    $p = trim($p);
                    if ($p !== '') {
                        $recipients[] = ['phone' => $p, 'values' => []];
                    }
                }
            }
        }

        // If 'recipients' (Repeater) was used
        if (! empty($data['recipients']) && is_array($data['recipients'])) {
            foreach ($data['recipients'] as $row) {
                $phone = $row['phone'] ?? '';
                $values = $row['values'] ?? [];

                if (is_string($phone) && (str_contains($phone, ',') || str_contains($phone, "\n"))) {
                    $subPhones = preg_split('/[,\r\n]+/', trim($phone));
                    foreach ($subPhones as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $recipients[] = ['phone' => $p, 'values' => $values];
                        }
                    }
                } else {
                    $phone = trim((string) $phone);
                    if ($phone !== '') {
                        $recipients[] = ['phone' => $phone, 'values' => $values];
                    }
                }
            }
        }

        return $recipients;
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

        $senderId = (int) ($data['sender_id'] ?? 0);
        $sender = $senderId > 0 ? Sender::find($senderId) : null;

        if (! $sender || $sender->type === null) {
            return;
        }

        $resolver = OperatorRateResolver::forUser($user);
        $isMasking = $sender->type->isMasking();
        $rates = $resolver->ratesForUser($isMasking);

        if ($rates === []) {
            $typeName = $isMasking ? 'Masking' : 'Non-masking';
            $planName = $resolver->getPlanName();
            $planMsg = $planName ? " in your assigned tariff plan [{$planName}]" : ' in your tariff plan';

            Notification::make()
                ->title("{$typeName} rate not configured")
                ->body("Please set your {$typeName} rate{$planMsg} before sending messages.")
                ->icon(Heroicon::ExclamationTriangle)
                ->danger()
                ->send();

            $this->halt();
        }

        $maxRate = max($rates);

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function estimateRecipientCount(string $source, array $data): int
    {
        return match ($source) {
            'manual' => count($this->resolveManualRecipients($data)),
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
