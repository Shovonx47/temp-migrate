<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Models\Contact;
use App\Models\Message;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        dd($data);
        $source = $data['source'] ?? 'manual';
        $body = $data['body'] ?? '';
        $mapping = $data['mapping'] ?? [];
        $phoneColumn = $mapping['phone'] ?? null;
        $variableMappings = $mapping['variables'] ?? [];
        $variables = $data['variables'] ?? [];

        $recipients = match ($source) {
            'manual' => $data['recipients'] ?? [],
            'upload' => $this->resolveUploadRecipients(
                $data['file'] ?? [],
                $phoneColumn,
                $variableMappings
            ),
            'group' => $this->resolveGroupRecipients(
                $data['groups'] ?? [],
                $variableMappings
            ),
            default => [],
        };

        // Resolve body template for each recipient
        $data['recipients'] = array_map(
            fn (array $r): array => [
                'phone' => $r['phone'] ?? '',
                'body' => $this->resolveBody($body, $variables, $r['variables'] ?? []),
            ],
            $recipients
        );

        unset($data['file'], $data['mapping'], $data['groups']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $first = null;

        foreach ($data['recipients'] as $recipient) {
            $message = Message::create([
                'user_id' => auth()->id(),
                'sender_id' => $data['sender_id'] ?? null,
                'to' => $recipient['phone'],
                'body' => $recipient['body'],
                'status' => 'pending',
            ]);

            $first ??= $message;
        }

        return $first ?? new Message;
    }

    private function resolveUploadRecipients(array $file, ?string $phoneCol, array $varCols): array
    {
        $headers = $file['headers'] ?? [];
        $rows = $file['rows'] ?? [];

        return array_map(function (array $row) use ($headers, $phoneCol, $varCols): array {
            $lookup = array_combine($headers, array_pad($row, count($headers), ''));

            return [
                'phone' => $phoneCol ? ($lookup[$phoneCol] ?? '') : '',
                'variables' => array_map(
                    fn (?string $col) => $col ? ($lookup[$col] ?? '') : '',
                    $varCols
                ),
            ];
        }, $rows);
    }

    private function resolveGroupRecipients(array $groupIds, array $varCols): array
    {
        $contacts = Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->where('is_blocked', false)
            ->get();

        return $this->contactsToRecipients($contacts, $varCols);
    }

    private function contactsToRecipients(Collection $contacts, array $varCols): array
    {
        return $contacts->map(function (Contact $contact) use ($varCols): array {
            return [
                'phone' => $contact->phone,
                'variables' => array_map(
                    fn (?string $field) => $this->resolveContactField($contact, $field),
                    $varCols
                ),
            ];
        })->all();
    }

    private function resolveContactField(Contact $contact, ?string $field): string
    {
        if (! $field) {
            return '';
        }

        // Handle meta.* fields
        if (str_starts_with($field, 'meta.')) {
            $key = substr($field, 5);

            return (string) ($contact->meta[$key] ?? '');
        }

        // Direct attribute access
        return (string) ($contact->{$field} ?? '');
    }

    private function resolveBody(string $body, array $variables, array $values): string
    {
        foreach ($variables as $i => $name) {
            $body = str_replace(
                "{{$name}}",
                $values[$name] ?? $values[$i] ?? '',
                $body
            );
        }

        return $body;
    }
}
