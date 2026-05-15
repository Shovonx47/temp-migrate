<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Pages;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use App\Models\Message;
use App\Support\CostCalculator;
use App\Support\FileParser;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMessageOld extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $type = $this->data['type'] ?? null; // form-only field
        $recipients = $this->buildRecipients($type);

        if (empty($recipients)) {
            Notification::make()
                ->title('No valid recipients')
                ->danger()
                ->send();

            $this->halt();
        }

        $userId = auth()->id();
        $senderId = $data['sender_id'];
        $body = $data['body'];

        $rows = collect($recipients)->map(function ($r) use ($userId, $senderId, $body) {
            $renderedBody = self::renderBody($body, $r['variables'] ?? []);
            $cost = CostCalculator::calculate($renderedBody, 1)['per_recipient'];

            return [
                'user_id' => $userId,
                'sender_id' => $senderId,
                'to' => $r['phone'],
                'body' => $renderedBody,
                'cost' => $cost,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        // Chunked bulk insert
        $rows->chunk(1000)->each(fn ($chunk) => Message::insert($chunk->all()));

        $total = $rows->count();

        Notification::make()
            ->title("{$total} messages queued")
            ->success()
            ->send();

        // Return the first inserted message for the redirect target
        return Message::where('user_id', $userId)
            ->latest('id')
            ->first();
    }

    protected function buildRecipients(?string $type): array
    {
        return match ($type) {
            'manual' => collect($this->data['manual_numbers'] ?? [])
                ->map(fn ($n) => FileParser::formatPhone($n))
                ->filter()
                ->unique()
                ->map(fn ($phone) => ['phone' => $phone, 'variables' => []])
                ->values()
                ->all(),

            'group' => $this->buildGroupRecipients(),

            'upload' => FileParser::applyMapping(
                $this->data['file_all_rows'] ?? [],
                $this->data['mapping'] ?? []
            ),

            default => [],
        };
    }

    protected function buildGroupRecipients(): array
    {
        $groupId = $this->data['group_id'] ?? null;

        if (! $groupId) {
            return [];
        }

        $group = auth()->user()->groups()->find($groupId);

        if (! $group) {
            return [];
        }

        return $group->contacts
            ->map(function ($contact) {
                $phone = FileParser::formatPhone($contact->phone);

                if (! $phone) {
                    return null;
                }

                return [
                    'phone' => $phone,
                    'variables' => [
                        'name' => $contact->name ?? '',
                        // add more contact attributes as your schema grows
                    ],
                ];
            })
            ->filter()
            ->unique('phone')
            ->values()
            ->all();
    }

    protected static function renderBody(string $template, array $variables): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($variables) {
            return $variables[$m[1]] ?? '';
        }, $template);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
