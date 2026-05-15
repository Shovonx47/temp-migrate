<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Schemas;

use App\Support\CostCalculator;
use App\Support\FileParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make([
                    self::composeStep(),
                    self::recipientsStep(),
                    self::mappingStep(),
                    self::reviewStep(),
                ])
                    ->skippable(false)
                    ->persistStepInQueryString(),
            ]);
    }

    protected static function composeStep(): Step
    {
        return Step::make('Compose')
            ->icon('heroicon-o-pencil-square')
            ->schema([
                Grid::make()->schema([
                    Select::make('sender_id')
                        ->label('Sender')
                        ->options(fn (): array => auth()->user()
                            ->senders()
                            ->select('senders.sender_id', 'senders.id')
                            ->pluck('sender_id', 'id')
                            ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('source')
                        ->label('Source')
                        ->options([
                            'manual' => 'Manual',
                            'group' => 'Group',
                            'upload' => 'Upload',
                        ])
                        ->default('manual')
                        ->live()
                        ->required()
                        ->dehydrated(false),
                ]),

                Textarea::make('body')
                    ->label('Message')
                    ->placeholder('Hi {name}, your order {order_id} is confirmed.')
                    ->helperText('Use {variable_name} for dynamic placeholders.')
                    ->rows(4)
                    ->live(debounce: 500)
                    ->required(),

                TextEntry::make('detected_variables')
                    ->label('Detected variables')
                    ->state(function (Get $get): HtmlString {
                        $vars = FileParser::extractVariables($get('body'));

                        if (empty($vars)) {
                            return new HtmlString('<span class="text-sm text-gray-500">No variables detected.</span>');
                        }

                        $badges = collect($vars)
                            ->map(fn($v) => '<span class="fi-color fi-color-primary fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm">{' . e($v) . '}</span>')
                            ->implode('');

                        return new HtmlString($badges);
                    })
                    ->html(),

                TextEntry::make('cost_estimate')
                    ->label('SMS info')
                    ->state(function (Get $get): HtmlString {
                        $cost = CostCalculator::calculate($get('body'), 1);
                        $encoding = $cost['is_unicode'] ? 'Unicode' : 'GSM-7';

                        return new HtmlString(
                            "<div class='text-sm space-y-1'>"
                            . "<div><strong>{$cost['char_count']}</strong> chars · <strong>{$cost['parts']}</strong> SMS part(s) · {$encoding}</div>"
                            . "<div class='text-gray-500'>Per recipient: ৳" . number_format($cost['per_recipient'], 2) . "</div>"
                            . "</div>"
                        );
                    })
                    ->html(),
            ]);
    }

    protected static function recipientsStep(): Step
    {
        return Step::make('Recipients')
            ->icon('heroicon-o-users')
            ->schema([
                // MANUAL
                Section::make('Manual entry')
                    ->visible(fn(Get $get) => $get('source') === 'manual')
                    ->schema([
                        TagsInput::make('manual_numbers')
                            ->label('Phone numbers')
                            ->placeholder('Type a number and press Enter')
                            ->splitKeys(['Tab', 'Enter', ' ', ','])
                            ->reorderable()
                            ->nestedRecursiveRules(['phone:BD,mobile'])
                            ->validationMessages(['phone' => 'Invalid BD phone number'])
                            ->required(fn(Get $get) => $get('source') === 'manual')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!is_array($state)) {
                                    return;
                                }

                                $formatted = collect($state)
                                    ->map(fn($n) => FileParser::formatPhone($n))
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->all();

                                $set('manual_numbers', $formatted);
                            }),
                    ]),

                // GROUP
                Section::make('Select group')
                    ->visible(fn(Get $get) => $get('source') === 'group')
                    ->schema([
                        Select::make('group_id')
                            ->label('Group')
                            ->options(fn(): array => auth()->user()
                                ->groups()
                                ->latest()
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->getSearchResultsUsing(fn(string $search): array => auth()->user()
                                ->groups()
                                ->whereLike('name', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn(Get $get) => $get('source') === 'group')
                            ->dehydrated(false),

                        TextEntry::make('group_count')
                            ->label('Group size')
                            ->visible(fn(Get $get) => filled($get('group_id')))
                            ->state(function (Get $get): string {
                                $groupId = $get('group_id');

                                if (!$groupId) {
                                    return '—';
                                }

                                $count = auth()->user()
                                    ->groups()
                                    ->where('groups.id', $groupId)
                                    ->withCount('contacts')
                                    ->first()
                                    ?->contacts_count ?? 0;

                                return "{$count} contact(s)";
                            }),
                    ]),

                // UPLOAD
                Section::make('Upload file')
                    ->visible(fn(Get $get) => $get('source') === 'upload')
                    ->schema([
                        FileUpload::make('recipients_file')
                            ->label('CSV or Excel file')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(20480)
                            ->storeFiles(false)
                            ->required(fn(Get $get) => $get('source') === 'upload')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    $set('file_headers', []);
                                    $set('file_preview', []);
                                    $set('file_all_rows', []);
                                    $set('file_total', 0);

                                    return;
                                }

                                $file = is_array($state) ? reset($state) : $state;
                                $parsed = FileParser::parse($file);

                                $set('file_headers', $parsed['headers']);
                                $set('file_preview', $parsed['preview']);
                                $set('file_all_rows', $parsed['all_rows']);
                                $set('file_total', $parsed['total']);
                            }),

                        TextEntry::make('file_summary')
                            ->label('')
                            ->visible(fn(Get $get) => !empty($get('file_headers')))
                            ->state(function (Get $get): HtmlString {
                                $total = $get('file_total') ?? 0;
                                $cols = count($get('file_headers') ?? []);

                                return new HtmlString(
                                    "<div class='text-sm'><strong>{$total}</strong> rows · <strong>{$cols}</strong> columns detected. Continue to map them.</div>"
                                );
                            })
                            ->html(),
                    ]),
            ]);
    }

    protected static function mappingStep(): Step
    {
        return Step::make('Map columns')
            ->icon('heroicon-o-link')
            ->visible(fn(Get $get) => $get('source') === 'upload')
            ->schema([
                TextEntry::make('mapping_help')
                    ->hiddenLabel()
                    ->state('Map the phone column and any template variables to columns from your file.')
                    ->html(),

                Section::make('Required')
                    ->schema([
                        Select::make('mapping.phone')
                            ->label('Phone Number')
                            ->options(fn(Get $get) => self::columnOptions($get('file_headers') ?? []))
                            ->required(fn(Get $get) => $get('source') === 'upload')
                            ->live()
                            ->dehydrated(false),
                    ]),

                Section::make('Template variables')
                    ->visible(fn(Get $get) => !empty(FileParser::extractVariables($get('body'))))
                    ->schema(function (Get $get): array {
                        $variables = FileParser::extractVariables($get('body'));
                        $headers = $get('file_headers') ?? [];
                        $options = self::columnOptions($headers);

                        return collect($variables)
                            ->map(fn($var) => Select::make("mapping.variables.{$var}")
                                ->label(new HtmlString("{" . e($var) . "}"))
                                ->options($options)
                                ->placeholder('— Skip this variable —')
                                ->live()
                                ->dehydrated(false)
                            )
                            ->all();
                    }),

                TextEntry::make('mapping_preview')
                    ->label('Live preview')
                    ->state(function (Get $get): HtmlString {
                        $mapping = $get('mapping') ?? [];
                        $rows = $get('file_preview') ?? [];
                        $body = $get('body') ?? '';

                        if (empty($mapping['phone']) || empty($rows)) {
                            return new HtmlString('<em class="text-sm text-gray-500">Select a phone column to see preview.</em>');
                        }

                        $html = '<div class="space-y-2">';
                        foreach (array_slice($rows, 0, 3) as $row) {
                            $phone = FileParser::formatPhone($row[$mapping['phone']] ?? '');
                            $rendered = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($row, $mapping) {
                                $col = $mapping['variables'][$m[1]] ?? null;

                                return $col !== null ? e($row[$col] ?? '') : $m[0];
                            }, $body);

                            $phoneDisplay = $phone ?: '<span class="text-red-500">invalid</span>';
                            $html .= "<div class='p-3 rounded border border-gray-200 dark:border-gray-700 text-sm'>"
                                . "<div class='font-mono text-xs text-gray-500'>To: {$phoneDisplay}</div>"
                                . "<div class='mt-1'>" . e($rendered) . "</div>"
                                . "</div>";
                        }
                        $html .= '</div>';

                        return new HtmlString($html);
                    })
                    ->html(),
            ]);
    }

    protected static function reviewStep(): Step
    {
        return Step::make('Review')
            ->icon('heroicon-o-check-circle')
            ->schema([
                Fieldset::make()
                    ->label('Final Summary')
                    ->columns(5)
                    ->schema([
                        TextEntry::make('summary_sender')
                            ->label('Sender')
                            ->badge()
                            ->state(fn(Get $get) => auth()->user()
                                ->senders()
                                ->select('senders.sender_id', 'senders.id')
                                ->where('id', $get('sender_id'))
                                ->value('sender_id') ?? '—'
                            ),
                        TextEntry::make('summary_source')
                            ->label('Source')
                            ->badge()
                            ->state(fn(Get $get) => match ($get('source')) {
                                'manual' => 'Manual',
                                'group' => 'Group',
                                'upload' => 'Upload',
                                default => '—',
                            }),

                        TextEntry::make('summary_recipient')
                            ->label('Recipients')
                            ->badge()
                            ->state(fn(Get $get) => (string) self::estimateRecipientCount($get)),

                        TextEntry::make('summary_parts')
                            ->label('Parts')
                            ->badge()
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->tooltip('Number of SMS parts per recipient. Messages longer than 160 chars may be split into multiple parts.')
                            ->state(function (Get $get) {
                                $cost = CostCalculator::calculate($get('body'), 1);
                                return (string) $cost['parts'];
                            }),

                        TextEntry::make('summary_encoding')
                            ->label('Encoding')
                            ->badge()
                            ->state(function (Get $get) {
                                $cost = CostCalculator::calculate($get('body'), 1);
                                return $cost['is_unicode'] ? 'Unicode' : 'GSM-7';
                            }),

                        TextEntry::make('summary_cost')
                            ->label('Cost')
                            ->money('BDT')
                            ->badge()
                            ->state(function (Get $get) {
                                $count = self::estimateRecipientCount($get);
                                $cost = CostCalculator::calculate($get('body'), $count);
                                return $cost['total'];
                            }),

                        Fieldset::make('review_body')
                            ->label('Message preview')
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('body_preview')
                                    ->hiddenLabel()
                                    ->state(fn(Get $get) => $get('body') ?? '')
                                    ->helperText('Variables will be replaced with actual values.'),
                            ]),
                    ]),

                Callout::make('cost_warning')
                    ->visible(function (Get $get): bool {
                        $count = self::estimateRecipientCount($get);
                        $cost = CostCalculator::calculate($get('body'), $count);
                        return $cost['total'] < 1000;
                    })
                    ->heading('Balance check')
                    ->description('This send exceeds ৳1,000. Add real balance check logic later.')
                    ->color('danger'),
            ]);
    }

    protected static function estimateRecipientCount(Get $get): int
    {
        return match ($get('source')) {
            'manual' => count($get('manual_numbers') ?? []),
            'group' => (int)(auth()->user()
                ->groups()
                ->where('groups.id', $get('group_id'))
                ->withCount('contacts')
                ->first()
                ?->contacts_count ?? 0),
            'upload' => count(FileParser::applyMapping(
                $get('file_all_rows') ?? [],
                $get('mapping') ?? []
            )),
            default => 0,
        };
    }

    protected static function columnOptions(array $headers): array
    {
        return collect($headers)
            ->mapWithKeys(fn($header, $index) => [$index => $header])
            ->all();
    }
}
