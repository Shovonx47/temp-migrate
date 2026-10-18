<?php

namespace App\Filament\Admin\Resources\Users\Customers\Schemas;

use App\Enums\TariffPlanStatus;
use App\Enums\UserStatus;
use App\Models\TariffPlan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Details')
                    ->description('Basic customer profile and access credentials.')
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Jahid Hasan')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->placeholder('e.g. jahid@example.com')
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
                Section::make('SMS Permissions')
                    ->description('Control what this customer is allowed to send and whether batches require admin approval.')
                    ->schema([
                        Toggle::make('api_access')
                            ->label('API Access')
                            ->helperText('Allow this customer to send SMS via the API.')
                            ->live()
                            ->inline(false),
                        Toggle::make('sms_auto_approved')
                            ->label('SMS Auto-approved')
                            ->helperText('When enabled, SMS batches from this customer are sent immediately without admin review.')
                            ->inline(false),
                    ])
                    ->columns(2),
                Section::make('Tariff Plan')
                    ->description('The tariff plan controls what this customer is charged per SMS part, per operator.')
                    ->schema([
                        Select::make('tariff_plan_id')
                            ->label('Current Plan')
                            ->placeholder('— Select a tariff plan —')
                            ->options(fn () => TariffPlan::query()
                                ->where('status', TariffPlanStatus::Active->value)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->dehydrated(false)
                            ->helperText('Selecting a tariff plan will activate it as this customer\'s current plan upon saving.'),
                    ]),
            ]);
    }
}
