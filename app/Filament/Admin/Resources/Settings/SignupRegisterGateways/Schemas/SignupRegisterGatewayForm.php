<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\SignupRegisterGateways\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SignupRegisterGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('HTTP API Configuration')
                ->description('Same payload as POST /api/v1/messages: bearer token, sender_id, numbers, and body. Used for register-page phone OTPs with priority failover.')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('name')
                            ->label('Gateway Name')
                            ->placeholder('e.g., Production WhiteInfo API')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('priority')
                            ->label('Priority')
                            ->helperText('Lower number = higher priority (1 is tried first, then 2, 3…).')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ]),

                    TextInput::make('endpoint_url')
                        ->label('Endpoint URL')
                        ->placeholder('https://sms.whiteinfobd.com/api/v1/messages')
                        ->url()
                        ->required()
                        ->maxLength(2048),

                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        Select::make('http_method')
                            ->label('HTTP Method')
                            ->options([
                                'POST' => 'POST (JSON body)',
                                'GET' => 'GET (query string)',
                            ])
                            ->default('POST')
                            ->required(),

                        TextInput::make('timeout_seconds')
                            ->label('Timeout (seconds)')
                            ->numeric()
                            ->default(15)
                            ->minValue(1)
                            ->maxValue(60)
                            ->required(),
                    ]),

                    TextInput::make('bearer_token')
                        ->label('Bearer Access Token')
                        ->password()
                        ->revealable()
                        ->helperText('Create this from a customer account: Developer → Access Tokens. Shown only when you type a new value.')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state)),

                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('sender_id')
                            ->label('Sender ID (label)')
                            ->placeholder('FROLAX')
                            ->helperText('The sender label from GET /api/v1/senders, not the numeric database id.')
                            ->required()
                            ->maxLength(255),

                        Select::make('phone_format')
                            ->label('Phone Format')
                            ->options([
                                'local_bd' => 'Local BD (017XXXXXXXX)',
                                'e164' => 'E.164 (+8801XXXXXXXXX)',
                            ])
                            ->default('local_bd')
                            ->required(),
                    ]),

                    Textarea::make('body_template')
                        ->label('OTP Message Template')
                        ->rows(3)
                        ->default('Your SMS WhiteInfo verification code is: {otp}. Valid for {minutes} minutes.')
                        ->helperText('Use {otp} for the code and {minutes} for expiry.')
                        ->required()
                        ->maxLength(1000),

                    Toggle::make('is_active')
                        ->label('Active Gateway')
                        ->helperText('Only active gateways are used during register OTP failover.')
                        ->default(true),
                ]),
        ]);
    }
}
