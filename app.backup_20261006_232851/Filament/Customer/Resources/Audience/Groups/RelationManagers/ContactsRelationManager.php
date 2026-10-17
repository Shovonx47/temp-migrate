<?php

namespace App\Filament\Customer\Resources\Audience\Groups\RelationManagers;

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use App\Filament\Exports\Customer\ContactExporter;
use App\Models\Contact;
use App\Models\Group;
use App\Support\RecipientFileParser;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $relatedResource = ContactResource::class;

    protected static ?string $title = 'Contacts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Contacts')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('add_numbers')
                    ->label('Add Phone Numbers')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('Add Phone Numbers to Group')
                    ->modalDescription('Type or paste phone numbers to add them to this group. You can enter single or multiple numbers separated by newlines, commas, or spaces.')
                    ->modalSubmitActionLabel('Add Numbers')
                    ->schema([
                        Textarea::make('numbers')
                            ->label('Phone Numbers')
                            ->placeholder("01712345678\n01812345678\n+8801912345678")
                            ->rows(6)
                            ->required()
                            ->helperText('Accepts 11-digit Bangladeshi numbers (e.g. 017XXXXXXXX, +8801XXXXXXXX). Separated by lines, commas, or spaces.'),
                        TextInput::make('name')
                            ->label('Contact Name (Optional)')
                            ->placeholder('Optional name for newly created contact(s)')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data) {
                        /** @var Group $group */
                        $group = $this->getOwnerRecord();
                        $userId = auth()->id() ?? $group->user_id;

                        $rawNumbers = (string) ($data['numbers'] ?? '');
                        $name = ! empty($data['name']) ? trim($data['name']) : null;
                        $tokens = preg_split('/[\r\n,;\s]+/', $rawNumbers, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                        $contactIds = [];
                        $validCount = 0;
                        $invalidCount = 0;

                        foreach ($tokens as $token) {
                            $cleaned = preg_replace('/[^\d+]/', '', trim($token));
                            if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                                $phone = '+880'.$m[1];

                                $contact = Contact::withoutGlobalScopes()
                                    ->where('user_id', $userId)
                                    ->where('phone', $phone)
                                    ->first();

                                if (! $contact) {
                                    $contact = Contact::create([
                                        'user_id' => $userId,
                                        'name' => $name,
                                        'phone' => $phone,
                                    ]);
                                } elseif ($name && empty($contact->name)) {
                                    $contact->update(['name' => $name]);
                                }

                                $contactIds[] = $contact->id;
                                $validCount++;
                            } else {
                                $invalidCount++;
                            }
                        }

                        if (! empty($contactIds)) {
                            foreach (array_chunk(array_unique($contactIds), 500) as $chunk) {
                                $group->contacts()->syncWithoutDetaching($chunk);
                            }
                        }

                        if ($validCount > 0) {
                            Notification::make()
                                ->title('Numbers Added')
                                ->success()
                                ->body("Successfully added {$validCount} phone " . str('number')->plural($validCount) . " to group '{$group->name}'.")
                                ->send();
                        }

                        if ($invalidCount > 0) {
                            Notification::make()
                                ->title('Invalid Numbers Skipped')
                                ->warning()
                                ->body("{$invalidCount} invalid " . str('number')->plural($invalidCount) . " could not be parsed.")
                                ->send();
                        }
                    }),

                Action::make('import_file')
                    ->label('Import Excel / CSV')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->color('success')
                    ->modalHeading('Import Contacts from Excel or CSV')
                    ->modalDescription('Upload a CSV or Excel spreadsheet. Phone numbers from any relevant columns will be extracted and added to this group.')
                    ->modalSubmitActionLabel('Start Import')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Excel / CSV File')
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
                            ->required()
                            ->helperText('Supports .csv, .xlsx, and .xls files. Column headers like Phone, Mobile, Contact, Number, and Name are automatically recognized.'),
                    ])
                    ->action(function (array $data) {
                        /** @var Group $group */
                        $group = $this->getOwnerRecord();
                        $userId = auth()->id() ?? $group->user_id;

                        $filePath = $this->resolveFileAbsolutePath($data['file'] ?? null);

                        if (! $filePath || ! file_exists($filePath)) {
                            Notification::make()
                                ->title('Import Failed')
                                ->danger()
                                ->body('The uploaded file could not be found or read.')
                                ->send();

                            return;
                        }

                        try {
                            $parser = app(RecipientFileParser::class);
                            $contactIds = [];
                            $validCount = 0;
                            $invalidCount = 0;

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
                                            'name' => $name,
                                            'phone' => $phone,
                                        ]);
                                    } elseif ($name && empty($contact->name)) {
                                        $contact->update(['name' => $name]);
                                    }

                                    $contactIds[] = $contact->id;
                                    $validCount++;
                                } else {
                                    $invalidCount++;
                                }
                            }

                            if (! empty($contactIds)) {
                                foreach (array_chunk(array_unique($contactIds), 500) as $chunk) {
                                    $group->contacts()->syncWithoutDetaching($chunk);
                                }
                            }

                            Notification::make()
                                ->title('Import Completed')
                                ->success()
                                ->body("Successfully imported {$validCount} phone " . str('number')->plural($validCount) . " into group '{$group->name}'.")
                                ->send();

                            if ($invalidCount > 0) {
                                Notification::make()
                                    ->title('Notice')
                                    ->warning()
                                    ->body("{$invalidCount} rows without valid phone numbers were skipped.")
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Import Error')
                                ->danger()
                                ->body('An error occurred during import: ' . $e->getMessage())
                                ->send();
                        }
                    }),

                CreateAction::make()
                    ->label('Create Contact')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id() ?? $this->getOwnerRecord()->user_id;

                        return $data;
                    }),

                AttachAction::make()
                    ->label('Attach Existing')
                    ->preloadRecordSelect(),

                ExportAction::make('export')
                    ->label('Export Contacts')
                    ->exporter(ContactExporter::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DetachAction::make()->label('Remove from Group'),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Remove from Group'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

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

        // 1. Look for phone column by name
        $phoneKeys = ['phone', 'phone_number', 'phonenumber', 'mobile', 'mobile_number', 'mobilenumber', 'contact', 'contact_number', 'recipient', 'number', 'msisdn', 'cell', 'tel'];
        $rawPhone = null;

        foreach ($phoneKeys as $k) {
            if (! empty($normalizedRow[$k])) {
                $rawPhone = (string) $normalizedRow[$k];
                break;
            }
        }

        // 2. If not found in named column, scan all cells for phone regex
        if (! $rawPhone) {
            foreach ($row as $cell) {
                $cellStr = preg_replace('/[^\d+]/', '', trim((string) $cell));
                if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cellStr)) {
                    $rawPhone = $cellStr;
                    break;
                }
            }
        }

        // Validate and format phone
        $formattedPhone = null;
        if ($rawPhone) {
            $cleaned = preg_replace('/[^\d+]/', '', $rawPhone);
            if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                $formattedPhone = '+880'.$m[1];
            }
        }

        // Look for name
        $nameKeys = ['name', 'full_name', 'fullname', 'contact_name', 'contactname', 'customer_name', 'client_name', 'recipient_name'];
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
