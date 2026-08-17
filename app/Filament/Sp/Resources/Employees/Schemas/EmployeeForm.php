<?php

namespace App\Filament\Sp\Resources\Employees\Schemas;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Details')
                    ->description('Basic employee profile and login credentials. Employees can access this S P panel.')
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Nusrat Jahan')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->placeholder('e.g. nusrat@example.com')
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
            ]);
    }

    /**
     * Persist the fixed S P panel attributes after the form has saved.
     */
    public static function persistAttributes(\Filament\Forms\ComponentContainer $container, \App\Models\User $employee): void
    {
        $employee->update([
            'type' => UserType::Sp,
            'sp_id' => auth()->id(),
        ]);
    }
}
