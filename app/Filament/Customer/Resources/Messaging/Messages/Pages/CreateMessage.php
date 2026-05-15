<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Models\Contact;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $source = $data['source'] ?? 'manual';
        $body = $data['body'] ?? '';
        $mapping = $data['mapping'] ?? [];
        $phoneColumn = $mapping['phone'] ?? null;
        $variableMappings = $mapping['variables'] ?? [];
        $bodyVariables = $data['body_variables'] ?? [];

        $recipients = match ($source) {
            'manual' => $data['recipients'] ?? [],
            'upload' => $this->resolveUploadRecipients(
                $data['file'] ?? [],
                $phoneColumn,
                $variableMappings
            ),
            'group' => $this->resolveGroupRecipients($data['groups'] ?? []),
            default => [],
        };

        // Group source doesn't support variable substitution — send body as-is.
        $data['recipients'] = array_map(
            fn (array $r): array => [
                'phone' => $r['phone'] ?? '',
                'body' => $source === 'group'
                    ? $body
                    : $this->resolveBody($body, $bodyVariables, $r['values'] ?? []),
            ],
            $recipients
        );

        unset($data['file'], $data['mapping'], $data['groups'], $data['body_variables']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $now = now();
        $messages = [];
        $failed = [];

        foreach ($data['recipients'] as $recipient) {
            $phone = new PhoneNumber($recipient['phone'], 'BD');

            if (! $phone->isValid()) {
                $failed[] = [
                    'phone' => $recipient['phone'] ?? '',
                    'reason' => 'Invalid phone number format',
                ];

                continue;
            }

            $messages[] = [
                'user_id' => auth()->id(),
                'sender_id' => $data['sender_id'] ?? null,
                'to' => $phone,
                'body' => $recipient['body'],
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($failed)) {
            $reportFilename = $this->saveFailedReport($failed);

            Notification::make()
                ->icon(Heroicon::ExclamationTriangle)
                ->title('Some recipients were invalid')
                ->body('Please review the failed recipients and correct their phone numbers.')
                ->danger()
                ->actions([
                    Action::make('download_report')
                        ->label('Download Report')
                        ->icon(Heroicon::ArrowDownTray)
                        ->url(route('customer.messages.failed-report', ['filename' => $reportFilename]))
                        ->openUrlInNewTab(),
                ])
                ->sendToDatabase(auth()->user())
                ->send();
        }

        // Do not do anything now, currently i'm testing it.
        $this->halt(true);
        dd($messages, $failed);
    }

    private function resolveUploadRecipients(array $file, ?string $phoneCol, array $varCols): array
    {
        $headers = $file['headers'] ?? [];
        $rows = $file['rows'] ?? [];

        return array_map(function (array $row) use ($headers, $phoneCol, $varCols): array {
            $lookup = array_combine($headers, array_pad($row, count($headers), ''));

            return [
                'phone' => $phoneCol ? ($lookup[$phoneCol] ?? '') : '',
                'values' => array_map(
                    fn (?string $col) => $col ? ($lookup[$col] ?? '') : '',
                    $varCols
                ),
            ];
        }, $rows);
    }

    private function resolveGroupRecipients(array $groupIds): array
    {
        return Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false)
            ->get()
            ->map(fn (Contact $contact): array => ['phone' => $contact->phone])
            ->all();
    }

    private function resolveBody(string $body, array $bodyVariables, array $values): string
    {
        foreach ($bodyVariables as $name) {
            $body = str_replace("{{$name}}", $values[$name] ?? '', $body);
        }

        return $body;
    }

    /**
     * @param  array<int, array{phone: string, reason: string}>  $failed
     */
    private function saveFailedReport(array $failed): string
    {
        $filename = 'failed-recipients-'.now()->format('Ymd-His').'-'.Str::random(8).'.csv';
        $path = 'failed-reports/'.auth()->id().'/'.$filename;

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Phone', 'Reason']);
        foreach ($failed as $row) {
            fputcsv($handle, [$row['phone'], $row['reason']]);
        }
        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $filename;
    }
}
