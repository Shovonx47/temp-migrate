<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists\Schemas;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\IpUtils;

class IpWhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('ip_address')
                    ->label('Public IP Address / CIDR Block')
                    ->placeholder('e.g. 203.0.113.195 or 198.51.100.0/24')
                    ->required()
                    ->maxLength(45)
                    ->helperText('Enter your public, internet-facing IP address (IPv4/IPv6) or CIDR block. Private/local IPs (e.g. 192.168.x.x, 10.x.x.x) are strictly rejected.')
                    ->rules([
                        fn () => function (string $attribute, $value, Closure $fail) {
                            $val = trim((string) $value);

                            if (empty($val)) {
                                return;
                            }

                            // Single IP validation
                            if (! str_contains($val, '/')) {
                                if (! filter_var($val, FILTER_VALIDATE_IP)) {
                                    $fail('The :attribute must be a valid IPv4 or IPv6 address.');
                                    return;
                                }

                                // Reject private (RFC1918) and reserved (loopback 127.0.0.1, link-local, broadcast)
                                if (! filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                    $fail('Private or reserved IP addresses (e.g. 192.168.x.x, 10.x.x.x, 172.16.x.x, 127.0.0.1) cannot be used over the public internet. Please enter your public IP address.');
                                    return;
                                }

                                // Additional CGNAT and null route checks
                                if (IpUtils::checkIp($val, ['100.64.0.0/10', '0.0.0.0/8'])) {
                                    $fail('Carrier-grade NAT or reserved network addresses cannot be whitelisted.');
                                    return;
                                }

                                return;
                            }

                            // CIDR Range validation
                            $parts = explode('/', $val, 2);
                            if (count($parts) !== 2) {
                                $fail('Invalid CIDR block format.');
                                return;
                            }

                            [$ip, $mask] = $parts;
                            $mask = trim($mask);

                            if (! is_numeric($mask)) {
                                $fail('CIDR prefix length must be a number.');
                                return;
                            }

                            $mask = (int) $mask;

                            $isIpv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
                            $isIpv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

                            if (! $isIpv4 && ! $isIpv6) {
                                $fail('The base address in the CIDR block must be a valid IP address.');
                                return;
                            }

                            // Reject private / reserved base IPs
                            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                $fail('Private or reserved IP ranges (e.g. 192.168.x.x, 10.x.x.x, 172.16.x.x) cannot be whitelisted. Only public internet-facing IP blocks are allowed.');
                                return;
                            }

                            if ($isIpv4) {
                                if ($mask < 16 || $mask > 32) {
                                    $fail('For IPv4 CIDR blocks, the subnet mask must be between /16 and /32.');
                                    return;
                                }
                            } elseif ($isIpv6) {
                                if ($mask < 48 || $mask > 128) {
                                    $fail('For IPv6 CIDR blocks, the prefix length must be between /48 and /128.');
                                    return;
                                }
                            }
                        },
                    ]),

                TextInput::make('label')
                    ->label('Label / Description')
                    ->placeholder('e.g. Production API Server')
                    ->maxLength(100)
                    ->helperText('An optional label to identify this IP or server.'),
            ]);
    }
}
