<?php

namespace App\Filament\Admin\Pages\Security;

use App\Enums\SupermanNavigationGroup;
use App\Settings\SecuritySettings;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IpWhitelistPage extends SettingsPage
{
    protected static string $settings = SecuritySettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Security;

    protected static ?string $navigationLabel = 'IP Whitelist Policy';

    protected static ?string $title = 'API IP Whitelist & Security Policy';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'security/ip-whitelist';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->type?->isSuperman() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Direct API & SMS Sending Whitelist Policy')
                    ->description('Configure how the API Server handles direct customer software calls and SMS dispatch.')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->schema([
                        Toggle::make('ip_whitelist_enforced')
                            ->label('Enforce IP Whitelist for Direct API')
                            ->helperText('When enabled, incoming direct API calls (e.g. sending SMS, checking balance) must match an approved whitelisted IP for that customer.')
                            ->default(true),

                        Toggle::make('allow_all_when_whitelist_empty')
                            ->label('Allow All IPs if Customer Whitelist is Empty (Permissive Mode)')
                            ->helperText('If enabled, customers who have not yet added any IPs are permitted from any IP. If disabled (Strict), direct API access and SMS sending is completely blocked until they have at least one approved IP.')
                            ->default(false),

                        Toggle::make('auto_approve_api_requests')
                            ->label('Auto-Approve IP Whitelists Submitted via API')
                            ->helperText('If enabled, public IPs submitted via the Developer API (curl / POST) will automatically be approved and become active immediately without requiring manual review in the Approval Center.')
                            ->default(false),

                        TextInput::make('blocked_message')
                            ->label('Access Denied Error Message')
                            ->helperText('The error response message returned when an unauthorized or non-whitelisted IP attempts an API call.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Web Portal Bypass Configuration')
                    ->description('When customers use the Web Portal, requests travel: Customer Browser → Web Portal Backend → API Server. In this case, only the Web Portal Backend server IP reaches the API, so end-customer IP checks can be bypassed.')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                        Toggle::make('web_portal_bypass_enabled')
                            ->label('Bypass IP Whitelist for Web Portal Backend')
                            ->helperText('Allows requests originating from internal Web Portal backend servers to bypass customer IP whitelist checks.')
                            ->default(true),

                        Textarea::make('trusted_portal_ips')
                            ->label('Trusted Web Portal Server IPs / Subnets')
                            ->helperText('Enter the IP addresses of your internal Web Portal backend servers (one per line or comma-separated, e.g. 127.0.0.1, 10.0.1.5). Calls originating from these IPs are recognized as Web Portal traffic.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
