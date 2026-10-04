<?php

namespace App\Filament\Customer\Resources\Audience\Groups\Pages;

use App\Filament\Customer\Resources\Audience\Groups\GroupResource;
use App\Models\Contact;
use App\Support\RecipientFileParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;

    public function form(Schema $schema): Schema
    {
        // Rebuild the Group Details section inline, then append the
        // optional "Add Contacts" section so users can seed contacts
        // immediately when creating the group.
        return $schema->components([
            Section::make('Group Details')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Group Name')
                        ->placeholder('Enter group name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Enter group description')
                        ->maxLength(500),
                ]),

            Section::make('Add Contacts (Optional)')
                ->description('You can add contacts right now while creating the group. You can also add or import more contacts later from the Edit page.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('_phone_numbers')
                        ->label('Phone Numbers')
                        ->placeholder("01712345678\n01812345678, 01912345678\n+8801312345678")
                        ->rows(5)
                        ->helperText('Enter one or multiple numbers separated by newlines, commas, or spaces. Accepts 11-digit Bangladeshi numbers (017XXXXXXXX, +8801XXXXXXXX).')
                        ->hint('Manual entry')
                        ->hintIcon('heroicon-o-pencil'),

                    FileUpload::make('_import_file')
                        ->label('Or Import from Excel / CSV')
                        ->disk('local')
                        ->directory('uploads/group-imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/csv',
                            'text/x-csv',
                            'application/x-csv',
                        ])
                        ->helperText('Supports .csv, .xlsx, and .xls files. Columns like Phone, Mobile, Contact, Number, Name are auto-detected.')
                        ->hint('Bulk import')
                        ->hintIcon('heroicon-o-document-arrow-up'),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        $group = $this->getRecord();
        $userId = auth()->id() ?? $group->user_id;

        $contactIds = [];
        $totalValid = 0;
        $totalInvalid = 0;

        // ── 1. Process manually entered phone numbers ──────────────────────
        $rawNumbers = (string) ($data['_phone_numbers'] ?? '');

        if (filled($rawNumbers)) {
            $tokens = preg_split('/[\r\n,;\s]+/', $rawNumbers, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $cleaned = preg_replace('/[^\d+]/', '', trim($token));
                if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                    $phone = '+880' . $m[1];

                    $contact = Contact::withoutGlobalScopes()
                        ->where('user_id', $userId)
                        ->where('phone', $phone)
                        ->first();

                    if (! $contact) {
                        $contact = Contact::create([
                            'user_id' => $userId,
                            'phone'   => $phone,
                        ]);
                    }

                    $contactIds[] = $contact->id;
                    $totalValid++;
                } else {
                    $totalInvalid++;
                }
            }
        }

        // ── 2. Process uploaded CSV / Excel file ───────────────────────────
        $fileState = $data['_import_file'] ?? null;
        $filePath  = $this->resolveFileAbsolutePath($fileState);

        if ($filePath && file_exists($filePath)) {
            try {
                $parser = app(RecipientFileParser::class);

                foreach ($parser->stream($filePath) as $row) {
                    [$phone, $name] = $this->extractContactFromRow($row);

                    if ($phone) {
                        $contact = Contact::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('phone', $phone)
                            ->first();

                        if (! $contact) {
                            $contact = Contact::create([
                                'user_id' => $userId,
                                'name'    => $name,
                                'phone'   => $phone,
                            ]);
                        } elseif ($name && empty($contact->name)) {
                            $contact->update(['name' => $name]);
                        }

                        $contactIds[] = $contact->id;
                        $totalValid++;
                    } else {
                        $totalInvalid++;
                    }
                }
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Import Error')
                    ->danger()
                    ->body('An error occurred while reading your file: ' . $e->getMessage())
                    ->send();
            }
        }

        // ── 3. Attach all collected contacts to the new group ─────────────
        if (! empty($contactIds)) {
            foreach (array_chunk(array_unique($contactIds), 500) as $chunk) {
                $group->contacts()->syncWithoutDetaching($chunk);
            }

            Notification::make()
                ->title('Contacts Added')
                ->success()
                ->body("Successfully added {$totalValid} " . str('contact')->plural($totalValid) . " to group '{$group->name}'.")
                ->send();
        }

        if ($totalInvalid > 0) {
            Notification::make()
                ->title('Some Entries Skipped')
                ->warning()
                ->body("{$totalInvalid} invalid or unreadable " . str('entry')->plural($totalInvalid) . ' could not be processed.')
                ->send();
        }
    }

    // ── Helpers (mirrored from ContactsRelationManager) ────────────────────

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: string|null, 1: string|null}
     */
    private function extractContactFromRow(array $row): array
    {
        $normalizedRow = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim((string) $k));
            $key = str_replace(['/', '-', ' '], '_', $key);
            $normalizedRow[$key] = is_string($v) ? trim($v) : $v;
        }

        $phoneKeys = ['phone', 'phone_number', 'phonenumber', 'mobile', 'mobile_number', 'mobilenumber',
            'contact', 'contact_number', 'recipient', 'number', 'msisdn', 'cell', 'tel'];
        $rawPhone = null;

        foreach ($phoneKeys as $k) {
            if (! empty($normalizedRow[$k])) {
                $rawPhone = (string) $normalizedRow[$k];
                break;
            }
        }

        if (! $rawPhone) {
            foreach ($row as $cell) {
                $cellStr = preg_replace('/[^\d+]/', '', trim((string) $cell));
                if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cellStr)) {
                    $rawPhone = $cellStr;
                    break;
                }
            }
        }

        $formattedPhone = null;
        if ($rawPhone) {
            $cleaned = preg_replace('/[^\d+]/', '', $rawPhone);
            if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                $formattedPhone = '+880' . $m[1];
            }
        }

        $nameKeys = ['name', 'full_name', 'fullname', 'contact_name', 'contactname',
            'customer_name', 'client_name', 'recipient_name'];
        $name = null;
        foreach ($nameKeys as $k) {
            if (! empty($normalizedRow[$k])) {
                $name = trim((string) $normalizedRow[$k]);
                break;
            }
        }

        return [$formattedPhone, $name];
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

