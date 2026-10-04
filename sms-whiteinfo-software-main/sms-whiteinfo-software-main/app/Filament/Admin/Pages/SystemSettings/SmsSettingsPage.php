<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use App\Models\Sender;
use App\Settings\SmsSettings;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SmsSettingsPage extends SettingsPage
{
    protected static string $settings = SmsSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeft;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'SMS Settings';

    protected static ?string $title = 'SMS Technical Settings';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'system-settings/sms';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->type?->isSuperman() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('SMS Configuration')
                    ->tabs([
                        Tab::make('General & Dispatch')
                            ->icon(Heroicon::OutlinedCog6Tooth)
                            ->schema([
                                Section::make('Global SMS Processing')
                                    ->description('Enable or disable sending system-wide.')
                                    ->schema([
                                        Toggle::make('sms_enabled')
                                            ->label('SMS Sending Enabled')
                                            ->helperText('Global kill-switch. When disabled, all outbound SMS dispatches are blocked.')
                                            ->default(true),
                                        Grid::make(2)->schema([
                                            Select::make('default_sms_type')
                                                ->label('Default SMS Type')
                                                ->options([
                                                    'plain_text' => 'Plain Text (GSM-7 / Standard)',
                                                    'masking' => 'Masking SMS',
                                                    'non_masking' => 'Non-Masking SMS',
                                                    'transactional' => 'Transactional (Priority)',
                                                    'promotional' => 'Promotional (Bulk)',
                                                ])
                                                ->default('plain_text')
                                                ->required(),
                                            Select::make('default_sender_id')
                                                ->label('Default Fallback Sender ID / CLI')
                                                ->options(fn () => Sender::where('is_enabled', true)->pluck('sender_id', 'sender_id'))
                                                ->searchable()
                                                ->placeholder('Select default CLI (optional)')
                                                ->helperText('Used when a campaign or API request does not supply a sender CLI.'),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Encoding & Unicode')
                            ->icon(Heroicon::OutlinedLanguage)
                            ->schema([
                                Section::make('Character Encoding & Character Sets')
                                    ->description('Configure character set negotiation and UCS-2 / GSM-7 behavior.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('default_encoding')
                                                ->label('Default Character Encoding')
                                                ->options([
                                                    'GSM-7' => 'GSM 7-bit Default Alphabet',
                                                    'UCS-2' => 'UCS-2 / UTF-16 (Unicode)',
                                                    'UTF-8' => 'UTF-8 Automatic',
                                                ])
                                                ->default('GSM-7')
                                                ->required(),
                                            Toggle::make('auto_detect_unicode')
                                                ->label('Auto-Detect Unicode')
                                                ->helperText('Automatically switch to UCS-2 encoding when non-GSM characters are detected.')
                                                ->default(true),
                                        ]),
                                        Grid::make(2)->schema([
                                            Toggle::make('force_unicode')
                                                ->label('Force Unicode for all Messages')
                                                ->helperText('Always send as UCS-2 regardless of message content.')
                                                ->default(false),
                                            Toggle::make('strip_incompatible_chars')
                                                ->label('Transliterate / Strip Incompatible Characters')
                                                ->helperText('Replace smart quotes, accents, and symbols with nearest GSM-7 equivalents.')
                                                ->default(false),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Part Limits & Concatenation')
                            ->icon(Heroicon::OutlinedDocumentDuplicate)
                            ->schema([
                                Section::make('Long SMS & Concatenation Limits')
                                    ->description('Protect against accidental credit drainage from massive concatenated messages.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('allow_long_sms')
                                                ->label('Allow Multi-Part (Long) SMS')
                                                ->helperText('When disabled, messages exceeding 160 GSM-7 (or 70 UCS-2) characters will be rejected.')
                                                ->default(true),
                                            TextInput::make('max_sms_parts')
                                                ->label('Maximum SMS Parts Limit')
                                                ->helperText('Upper boundary on concatenated segments (e.g. 10 parts = 1,530 GSM chars).')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(20)
                                                ->default(10)
                                                ->required(),
                                        ]),
                                    ]),
                                Section::make('Duplicate SMS Protection')
                                    ->description('Prevent accidental double sends to the same recipient with identical text.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('duplicate_protection_enabled')
                                                ->label('Enable Duplicate SMS Protection')
                                                ->helperText('Rejects identical content sent to the same phone number within a time window.')
                                                ->default(false),
                                            TextInput::make('duplicate_window_seconds')
                                                ->label('Deduplication Window (Seconds)')
                                                ->helperText('Time window in seconds to block identical messages (e.g., 120s = 2 minutes).')
                                                ->numeric()
                                                ->minValue(10)
                                                ->maxValue(3600)
                                                ->default(120)
                                                ->required(),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Number Validation')
                            ->icon(Heroicon::OutlinedPhone)
                            ->schema([
                                Section::make('Recipient Phone Number Rules')
                                    ->description('Define mobile number normalization and country format validation.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('validation_mode')
                                                ->label('Validation Strategy')
                                                ->options([
                                                    'bangladesh_strict' => 'Bangladesh Strict (013-019 / +8801XXXXXXXXX)',
                                                    'international_e164' => 'International E.164 (libphonenumber)',
                                                ])
                                                ->default('bangladesh_strict')
                                                ->required(),
                                            TextInput::make('default_country_code')
                                                ->label('Default Country Code Prefix')
                                                ->placeholder('+880')
                                                ->default('+880')
                                                ->required(),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Logging & Message IDs')
                            ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                            ->schema([
                                Section::make('SMS Logging & Audit Trail')
                                    ->description('Configure system log verbosity and payload retention.')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('log_level')
                                                ->label('Minimum Log Level')
                                                ->options([
                                                    'debug' => 'Debug (All API transactions & payloads)',
                                                    'info' => 'Info (Standard message status changes)',
                                                    'warning' => 'Warning (Slow queries & retries)',
                                                    'error' => 'Error (Failures & exceptions only)',
                                                ])
                                                ->default('info')
                                                ->required(),
                                            Toggle::make('log_payloads')
                                                ->label('Record Request/Response Payloads')
                                                ->helperText('Store full JSON request and response payloads in sms_logs.')
                                                ->default(true),
                                            TextInput::make('log_retention_days')
                                                ->label('Log Retention (Days)')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(365)
                                                ->default(30)
                                                ->required(),
                                        ]),
                                    ]),
                                Section::make('Message ID Configuration')
                                    ->description('Customize external reference ID generation.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('message_id_prefix')
                                                ->label('Message ID Prefix')
                                                ->placeholder('SMS')
                                                ->default('SMS')
                                                ->required(),
                                            Select::make('message_id_format')
                                                ->label('Message ID Format')
                                                ->options([
                                                    'prefix_timestamp_random' => 'Prefix + Timestamp + Random (e.g. SMS-20260828-ABCD12)',
                                                    'uuid' => 'UUID v4 (e.g. 550e8400-e29b-41d4-a716-446655440000)',
                                                    'numeric' => 'Sequential Numeric ID',
                                                ])
                                                ->default('prefix_timestamp_random')
                                                ->required(),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
