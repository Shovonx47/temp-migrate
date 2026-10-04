<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\EmailGateways\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('SMTP Server Configuration')
                ->description('Configure SMTP gateway details, credentials, and priority order.')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('name')
                            ->label('Gateway Name')
                            ->placeholder('e.g., Primary Mailgun SMTP')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('priority')
                            ->label('Priority')
                            ->helperText('Lower number = higher priority (e.g., 1 is tried first, then 2, 3...).')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ]),

                    Grid::make([
                        'default' => 1,
                        'md' => 3,
                    ])->schema([
                        TextInput::make('host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.mailgun.org')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->default(587)
                            ->required(),

                        Select::make('encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                'none' => 'None / Plain',
                            ])
                            ->default('tls')
                            ->required(),
                    ]),

                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('username')
                            ->label('SMTP Username')
                            ->placeholder('postmaster@yourdomain.com')
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state)),
                    ]),

                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('from_address')
                            ->label('From Email Address')
                            ->email()
                            ->placeholder('noreply@yourdomain.com'),

                        TextInput::make('from_name')
                            ->label('From Sender Name')
                            ->placeholder('SMS WhiteInfo'),
                    ]),

                    Toggle::make('is_active')
                        ->label('Active Gateway')
                        ->helperText('Only active gateways are used during OTP delivery failover.')
                        ->default(true),
                ]),
        ]);
    }
}
