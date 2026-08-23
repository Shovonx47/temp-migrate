<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Schemas;

use App\Filament\Forms\Components\MappingComponent;
use App\Support\RecipientFileParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Exists;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Hidden::make('body_variables')->default([]),
                Hidden::make('file_headers')->default([]),
                Hidden::make('file_sample')->default([]),
                Hidden::make('file_total_rows')->default(0),

                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('sender_id')
                            ->label('Sender ID')
                            ->prefixIcon(Heroicon::Identification)
                            ->options(fn () => Auth::user()
                                ->senders()
                                ->select(['senders.sender_id', 'senders.id'])
                                ->pluck('sender_id', 'id')
                                ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->exists(
                                table: 'sender_user',
                                column: 'sender_id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('user_id', Auth::id())
                            ),

                        Select::make('source')
                            ->label('Source')
                            ->prefixIcon(Heroicon::Inbox)
                            ->options([
                                'manual' => 'Manual',
                                'group' => 'Group',
                                'upload' => 'Upload',
                            ])
                            ->default('manual')
                            ->live()
                            ->required(),

                        // Recipients: Comma-separated Phone Numbers (when no template variables)
                        TagsInput::make('phones')
                            ->label('Phone Numbers')
                            ->placeholder('Type or paste numbers separated by commas, space or press Enter')
                            ->splitKeys(['Tab', ' ', ',', 'Enter'])
                            ->separator(',')
                            ->live()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('source') === 'manual' && empty($get('body_variables')))
                            ->required(fn (Get $get) => $get('source') === 'manual' && empty($get('body_variables'))),

                        // Recipients: Manual entry repeater (when template variables like {name} exist)
                        Repeater::make('recipients')
                            ->label('Recipients')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('source') === 'manual' && ! empty($get('body_variables')))
                            ->required(fn (Get $get) => $get('source') === 'manual' && ! empty($get('body_variables')))
                            ->table(function (Get $get) {
                                $columns = [
                                    Repeater\TableColumn::make('Phone')
                                        ->alignLeft()
                                        ->markAsRequired(),
                                ];

                                foreach ($get('body_variables') ?? [] as $variable) {
                                    $columns[] = Repeater\TableColumn::make(ucfirst($variable))
                                        ->alignLeft()
                                        ->markAsRequired();
                                }

                                return $columns;
                            })
                            ->schema(function (Get $get) {
                                $fields = [
                                    TextInput::make('phone')
                                        ->placeholder('Type or paste phone numbers')
                                        ->required(),
                                ];

                                foreach ($get('body_variables') ?? [] as $variable) {
                                    $fields[] = TextInput::make("values.{$variable}")
                                        ->label(ucfirst($variable))
                                        ->placeholder("Value for {$variable}")
                                        ->required();
                                }

                                return $fields;
                            }),

                        // Recipients: Group selection
                        Select::make('groups')
                            ->label('Groups')
                            ->columnSpanFull()
                            ->options(fn (): array => auth()->user()
                                ->groups()
                                ->latest()
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->getSearchResultsUsing(fn (string $search): array => auth()->user()
                                ->groups()
                                ->whereLike('name', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get) => $get('source') === 'group')
                            ->required(fn (Get $get) => $get('source') === 'group'),

                        // Recipients: Upload file
                        FileUpload::make('file')
                            ->label('File')
                            ->columnSpanFull()
                            ->disk('local')
                            ->directory('uploads/recipient-files')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->helperText('Upload a CSV or Excel file with a "phone" column and any variables used in your message body.')
                            ->maxSize(100 * 1024) // 100 MB
                            ->preserveFilenames()
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set) => self::parseUploadedFile($state, $set))
                            ->dehydrated()
                            ->visible(fn (Get $get) => $get('source') === 'upload')
                            ->required(fn (Get $get) => $get('source') === 'upload'),

                        View::make('filament.schemas.components.upload-preview')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('source') === 'upload'
                                && ! empty($get('file_headers'))
                            ),

                        // Mapping field for upload source
                        MappingComponent::make('mapping')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->visible(fn (Get $get) => $get('source') === 'upload'
                                && ! empty($get('file_headers'))
                            ),

                        // Message Body
                        Textarea::make('body')
                            ->label('Message')
                            ->placeholder('Hi {name}, your order {order_id} is confirmed.')
                            ->columnSpanFull()
                            ->rows(5)
                            ->required()
                            ->live(onBlur: true)
                            ->extraInputAttributes(['data-sms-body' => 'true'])
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                preg_match_all('/{(\w+)}/', $state ?? '', $matches);
                                $variables = array_values(array_unique($matches[1] ?? []));
                                $set('body_variables', $variables);
                            })
                            ->afterStateUpdatedJs(<<<'JS'
                                const matches = ($state ?? '').matchAll(/{(\w+)}/g);
                                const variables = [...new Set([...matches].map(m => m[1]))];
                                $set('body_variables', variables);
                            JS
                            ),

                        View::make('filament.schemas.components.sms-stats')
                            ->columnSpanFull(),

                        Callout::make('Variables are not supported for group messages.')
                            ->description(function (Get $get): HtmlString {
                                $vars = $get('body_variables') ?? [];
                                $list = implode(', ', array_map(fn (string $v) => '<strong>{'.$v.'}</strong>', $vars));

                                return new HtmlString("Your message contains $list — these placeholders will be sent as-is to every contact. Switch to Upload or Manual if you need per-recipient values.");
                            })
                            ->warning()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('source') === 'group'
                                && ! empty($get('body_variables'))
                            ),
                    ]),
            ]);
    }

    /**
     * Resolve the just-uploaded file and feed its headers + sample rows back into form state
     * so the mapping step can render without re-parsing.
     */
    private static function parseUploadedFile(mixed $state, Set $set): void
    {
        $absolutePath = self::resolveUploadAbsolutePath($state);

        if ($absolutePath === null) {
            $set('file_headers', []);
            $set('file_sample', []);
            $set('file_total_rows', 0);

            return;
        }

        try {
            $preview = app(RecipientFileParser::class)->peek($absolutePath);
            $set('file_headers', $preview['headers']);
            $set('file_sample', $preview['sample']);
            $set('file_total_rows', $preview['total_rows']);
        } catch (Throwable) {
            $set('file_headers', []);
            $set('file_sample', []);
            $set('file_total_rows', 0);
        }
    }

    private static function resolveUploadAbsolutePath(mixed $state): ?string
    {
        if ($state === null || $state === '' || $state === []) {
            return null;
        }

        if (is_array($state)) {
            $state = reset($state);
        }

        if ($state instanceof TemporaryUploadedFile) {
            return $state->getRealPath();
        }

        if (is_string($state)) {
            return Storage::disk('local')->exists($state)
                ? Storage::disk('local')->path($state)
                : null;
        }

        return null;
    }
}
