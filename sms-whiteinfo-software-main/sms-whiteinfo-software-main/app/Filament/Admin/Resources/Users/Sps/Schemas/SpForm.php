<?php

namespace App\Filament\Admin\Resources\Users\Sps\Schemas;

use App\Enums\TariffPlanStatus;
use App\Enums\UserStatus;
use App\Models\Scopes\ScopedBySp;
use App\Models\TariffPlan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class SpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Details')
                    ->description('Basic S P profile and access credentials.')
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Acme Communications')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->placeholder('e.g. sp@example.com')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        PhoneInput::make('phone')
                            ->placeholder('e.g. 01700000000')
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->placeholder('Select an approval status')
                            ->options(UserStatus::class)
                            ->default(UserStatus::Approved)
                            ->required(),
                        TextInput::make('password')
                            ->placeholder('Enter a strong password')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Leave blank to keep the current password.'
                                : null),
                    ])
                    ->columns(2),
                Section::make('Permissions')
                    ->schema([
                        Toggle::make('api_access')
                            ->label('API Access')
                            ->helperText('Allow this S P to use API features when enabled later.')
                            ->inline(false),
                        Toggle::make('sms_auto_approved')
                            ->label('SMS Auto-approved')
                            ->helperText('Default auto-approval preference for this S P account.')
                            ->inline(false),
                    ])
                    ->columns(2),
                Section::make('Wholesale Tariff Plan')
                    ->description('Wholesale rates charged to this S P for margin reporting and retail floor guidance.')
                    ->schema([
                        Select::make('tariff_plan_id')
                            ->label('Current Wholesale Plan')
                            ->placeholder('— Select a tariff plan —')
                            ->options(fn () => TariffPlan::query()
                                ->withoutGlobalScopes([ScopedBySp::class])
                                ->whereNull('sp_id')
                                ->where('status', TariffPlanStatus::Active->value)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->dehydrated(false)
                            ->helperText('Selecting a plan activates it as this S P\'s wholesale plan upon saving.'),
                    ]),
            ]);
    }
}
