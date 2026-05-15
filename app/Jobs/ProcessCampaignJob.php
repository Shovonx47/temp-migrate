<?php

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\User;
use App\Services\RecipientFileParser;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class ProcessCampaignJob implements ShouldQueue
{
    use Queueable;

    private const BATCH_SIZE = 1000;

    private const DEFAULT_COUNTRY = 'BD';

    public int $timeout = 0;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $config
     *                                        Source-specific config:
     *                                        - manual: ['recipients' => array<int, array{phone: string, values: array<string,string>}>]
     *                                        - upload: ['file_path' => string, 'disk' => string, 'mapping' => array{phone: string, variables: array<string,string>}]
     *                                        - group:  ['group_ids' => int[]]
     * @param  string[]  $bodyVariables
     */
    public function __construct(
        public readonly Campaign $campaign,
        public readonly string $sourceType,
        public readonly array $config,
        public readonly string $body,
        public readonly array $bodyVariables,
    ) {}

    public function handle(RecipientFileParser $parser): void
    {
        $campaign = $this->campaign->fresh();

        if (! $campaign) {
            return;
        }

        $campaign->update([
            'status' => CampaignStatus::Running,
            'started_at' => now(),
        ]);

        $batch = [];
        $failed = [];
        $successCount = 0;
        $now = now();

        try {
            foreach ($this->iterateRecipients($parser) as $recipient) {
                $phoneRaw = (string) ($recipient['phone'] ?? '');

                if ($phoneRaw === '') {
                    $failed[] = $this->failedRow($recipient, 'Phone number missing');

                    continue;
                }

                try {
                    $phone = new PhoneNumber($phoneRaw, self::DEFAULT_COUNTRY);
                    if (! $phone->isValid()) {
                        $failed[] = $this->failedRow($recipient, 'Invalid phone number format');

                        continue;
                    }
                } catch (Throwable) {
                    $failed[] = $this->failedRow($recipient, 'Invalid phone number format');

                    continue;
                }

                $batch[] = [
                    'user_id' => $campaign->user_id,
                    'campaign_id' => $campaign->id,
                    'sender_id' => $campaign->sender_id,
                    'to' => $phone->formatE164(),
                    'body' => $this->resolveBody($recipient),
                    'status' => 'pending',
                    'cost' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $successCount++;

                if (count($batch) >= self::BATCH_SIZE) {
                    Message::insert($batch);
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                Message::insert($batch);
            }

            $reportPath = ! empty($failed) ? $this->writeFailedReport($campaign, $failed) : null;

            $campaign->update([
                'status' => CampaignStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->notifyCompletion($campaign, $successCount, count($failed), $reportPath);
        } catch (Throwable $e) {
            $campaign->update([
                'status' => CampaignStatus::Failed,
                'completed_at' => now(),
            ]);
            $this->notifyFailure($campaign, $e);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $campaign = $this->campaign->fresh();
        if ($campaign && $campaign->status !== CampaignStatus::Failed) {
            $campaign->update([
                'status' => CampaignStatus::Failed,
                'completed_at' => now(),
            ]);
            $this->notifyFailure($campaign, $e);
        }
    }

    /**
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateRecipients(RecipientFileParser $parser): iterable
    {
        return match ($this->sourceType) {
            'manual' => $this->iterateManual(),
            'upload' => $this->iterateUpload($parser),
            'group' => $this->iterateGroup(),
            default => [],
        };
    }

    /**
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateManual(): iterable
    {
        foreach ($this->config['recipients'] ?? [] as $r) {
            yield [
                'phone' => (string) ($r['phone'] ?? ''),
                'values' => $r['values'] ?? [],
                'raw' => $r,
            ];
        }
    }

    /**
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateUpload(RecipientFileParser $parser): iterable
    {
        $disk = $this->config['disk'] ?? 'local';
        $relativePath = (string) ($this->config['file_path'] ?? '');
        $mapping = $this->config['mapping'] ?? [];
        $phoneCol = $mapping['phone'] ?? null;
        $variableCols = $mapping['variables'] ?? [];

        if ($relativePath === '' || ! Storage::disk($disk)->exists($relativePath)) {
            return;
        }

        $absolutePath = Storage::disk($disk)->path($relativePath);

        foreach ($parser->stream($absolutePath) as $row) {
            $values = [];
            foreach ($variableCols as $varName => $colName) {
                $values[$varName] = $colName ? ($row[$colName] ?? '') : '';
            }

            yield [
                'phone' => $phoneCol ? (string) ($row[$phoneCol] ?? '') : '',
                'values' => $values,
                'raw' => $row,
            ];
        }
    }

    /**
     * @return iterable<int, array{phone: string, values: array<string, string>, raw: array<string, mixed>}>
     */
    private function iterateGroup(): iterable
    {
        $groupIds = $this->config['group_ids'] ?? [];

        if (empty($groupIds)) {
            return;
        }

        $query = Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false);

        foreach ($query->lazy(1000) as $contact) {
            yield [
                'phone' => (string) $contact->phone,
                'values' => [],
                'raw' => ['phone' => (string) $contact->phone, 'name' => $contact->name ?? ''],
            ];
        }
    }

    /**
     * @param  array{phone: string, values: array<string, string>, raw: array<string, mixed>}  $recipient
     */
    private function resolveBody(array $recipient): string
    {
        // Group sends body verbatim; no per-recipient variable substitution.
        if ($this->sourceType === 'group') {
            return $this->body;
        }

        $body = $this->body;
        foreach ($this->bodyVariables as $var) {
            $body = str_replace('{'.$var.'}', $recipient['values'][$var] ?? '', $body);
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $recipient
     * @return array{phone: string, reason: string, raw: array<string, mixed>}
     */
    private function failedRow(array $recipient, string $reason): array
    {
        return [
            'phone' => (string) ($recipient['phone'] ?? ''),
            'reason' => $reason,
            'raw' => $recipient['raw'] ?? [],
        ];
    }

    /**
     * @param  array<int, array{phone: string, reason: string, raw: array<string, mixed>}>  $failed
     */
    private function writeFailedReport(Campaign $campaign, array $failed): string
    {
        $path = sprintf(
            'campaigns/%d/failed-recipients-%s.csv',
            $campaign->id,
            now()->format('Ymd-His'),
        );

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Phone', 'Reason'], escape: '\\');
        foreach ($failed as $row) {
            fputcsv($handle, [$row['phone'], $row['reason']], escape: '\\');
        }
        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }

    private function notifyCompletion(Campaign $campaign, int $sent, int $failed, ?string $reportPath): void
    {
        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        $title = "Campaign queued: {$sent} message".($sent === 1 ? '' : 's').' ready to send';
        $body = $failed > 0
            ? "{$failed} recipient".($failed === 1 ? '' : 's').' had invalid phone numbers and were skipped.'
            : 'All recipients were processed successfully.';

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->icon(Heroicon::PaperAirplane);

        $failed > 0 ? $notification->warning() : $notification->success();

        if ($reportPath !== null) {
            $notification->actions([
                Action::make('download_report')
                    ->label('Download failed recipients')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(route('customer.campaigns.failed-report', [
                        'campaign' => $campaign->id,
                        'filename' => basename($reportPath),
                    ]))
                    ->openUrlInNewTab(),
            ]);
        }

        $notification->sendToDatabase($user);
    }

    private function notifyFailure(Campaign $campaign, Throwable $e): void
    {
        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Campaign processing failed')
            ->body($e->getMessage())
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }
}
