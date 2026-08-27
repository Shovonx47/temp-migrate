<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists\Schemas;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IpWhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('ip_address')
                    ->label('IP Address / CIDR Block')
                    ->placeholder('e.g. 103.145.12.5 or 192.168.1.0/24')
                    ->required()
                    ->maxLength(45)
                    ->helperText('You can enter a single public IP (IPv4/IPv6) or a CIDR subnet block.')
                    ->rules([
                        fn () => function (string $attribute, $value, Closure $fail) {
                            $val = trim((string) $value);
                            if (filter_var($val, FILTER_VALIDATE_IP)) {
                                return;
                            }
                            if (str_contains($val, '/')) {
                                [$ip, $mask] = explode('/', $val, 2);
                                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && is_numeric($mask) && (int) $mask >= 0 && (int) $mask <= 32) {
                                    return;
                                }
                                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && is_numeric($mask) && (int) $mask >= 0 && (int) $mask <= 128) {
                                    return;
                                }
                            }
                            $fail('The :attribute must be a valid IPv4/IPv6 address or CIDR range.');
                        },
                    ]),

                TextInput::make('label')
                    ->label('Label / Description')
                    ->placeholder('e.g. Production Web Server')
                    ->maxLength(100)
                    ->helperText('An optional label to help you identify this IP address.'),
            ]);
    }
}
