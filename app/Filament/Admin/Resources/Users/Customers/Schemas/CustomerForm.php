<?php

namespace App\Filament\Admin\Resources\Users\Customers\Schemas;

use App\Enums\UserStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                            ->default(UserStatus::Pending)
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
                Section::make('Operator rate overrides')
                    ->description('Override the system-default operator rates for this customer. Leave a price blank to fall back to the operator default.')
                    ->schema([
                        Repeater::make('operatorRates')
                            ->relationship()
                            ->hiddenLabel()
                            ->table([
                                Repeater\TableColumn::make('Operator'),
                                Repeater\TableColumn::make('Masking Price'),
                                Repeater\TableColumn::make('Non-masking Price'),
                            ])
                            ->schema([
                                Select::make('operator_id')
                                    ->label('Operator')
                                    ->relationship('operator', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct(),
                                TextInput::make('masking_price')
                                    ->label('Masking (per part)')
                                    ->prefixIcon(Heroicon::Banknotes)
                                    ->prefix('৳')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0)
                                    ->nullable(),
                                TextInput::make('non_masking_price')
                                    ->label('Non-masking (per part)')
                                    ->prefixIcon(Heroicon::Banknotes)
                                    ->prefix('৳')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0)
                                    ->nullable(),
                            ])
                            ->addActionLabel('Add operator override')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
