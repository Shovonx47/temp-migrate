<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\UserStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Staff Details')
                    ->description('Internal company staff account details and credentials for Superman panel.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder('e.g. Raju Ahmed')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->placeholder('e.g. raju@example.com')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        PhoneInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder('e.g. 01700000000')
                            ->unique(ignoreRecord: true),

                        Select::make('sp_role_id')
                            ->label('Role')
                            ->placeholder('Select a role (leave empty for Super Admin)')
                            ->relationship(
                                name: 'spRole',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('scope', 'superman'),
                            )
                            ->helperText('Users without a role have full Super Admin access.')
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label('Status')
                            ->options(UserStatus::class)
                            ->default(UserStatus::Approved)
                            ->required(),

                        TextInput::make('password')
                            ->label('Password')
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
}
