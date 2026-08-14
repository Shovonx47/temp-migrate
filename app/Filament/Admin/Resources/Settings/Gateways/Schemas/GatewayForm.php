<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class GatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::gatewayDetailsSection(),
            self::configurationSection(),
        ]);
    }

    private static function gatewayDetailsSection(): Section
    {
        return Section::make('Gateway Details')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('driver')
                    ->options(self::getDriverOptions())
                    ->default(self::getDriverOptions()->keys()->first())
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateHydrated(function (Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && filled($state)) {
                            self::seedDriverState($set, $state);
                        }
                    })
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && filled($state)) {
                            self::seedDriverState($set, $state);
                        }
                    })
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->disabledOn('edit'),
                TextInput::make('requests_per_minute')
                    ->label('Requests Per Minute')
                    ->numeric()
                    ->default(60)
                    ->required(),
                Select::make('is_enabled')
                    ->boolean()
                    ->default(false)
                    ->required(),
                Select::make('supports_bulk')
                    ->default(false)
                    ->boolean()
                    ->required(),
            ]);
    }

    private static function configurationSection(): Section
    {
        return Section::make('Configuration')
            ->schema(function (Get $get): array {
                $driver = $get('driver');

                if (blank($driver)) {
                    return [];
                }

                $driverConfig = config("sms.drivers.$driver");

                if (blank($driverConfig)) {
                    return [];
                }

                return array_filter([
                    self::credentialsFieldset($driverConfig),
                    self::configFieldset($driverConfig),
                ]);
            });
    }

    private static function credentialsFieldset(array $driverConfig): ?Fieldset
    {
        $fields = self::buildFieldsFromConfig($driverConfig, 'credentials', 'Enter your');

        return empty($fields) ? null : Fieldset::make('Credentials')
            ->columns(1)
            ->schema($fields);
    }

    private static function configFieldset(array $driverConfig): ?Fieldset
    {
        $fields = self::buildFieldsFromConfig($driverConfig, 'config', 'Enter');

        return empty($fields) ? null : Fieldset::make('Options')
            ->columns(1)
            ->schema($fields);
    }

    private static function buildFieldsFromConfig(
        array $driverConfig,
        string $section,
        string $placeholderPrefix
    ): array {
        $fields = [];
        $config = data_get($driverConfig, $section, []);

        foreach ($config as $key => $options) {
            $type = $options['type'] ?? 'text';

            if ($type === 'hidden') {
                continue;
            }

            if ($type === 'multi_select') {
                $fields[] = Select::make("$section.$key")
                    ->label($options['label'] ?? ucfirst($key))
                    ->multiple()
                    ->options(function (Get $get) use ($section): array {
                        $apiType = $get("$section.api_type")
                            ?? $get('api_type')
                            ?? $get('credentials.api_type');

                        return self::getCarrierOptionsForApiType($apiType);
                    })
                    ->live()
                    ->required($options['required'] ?? false);

                continue;
            }

            if ($type === 'select') {
                $field = Select::make("$section.$key")
                    ->label($options['label'] ?? ucfirst($key))
                    ->options($options['options'] ?? [])
                    ->live()
                    ->afterStateUpdated(function (Set $set) use ($section, $key) {
                        if ($key === 'api_type') {
                            $set('enabled_carriers', []);
                            $set("$section.enabled_carriers", []);
                            $set('credentials.enabled_carriers', []);
                        }
                    })
                    ->required($options['required'] ?? false);

                if (isset($options['default'])) {
                    $field->default($options['default']);
                }

                $fields[] = $field;

                continue;
            }

            $field = TextInput::make("$section.$key")
                ->label($options['label'] ?? ucfirst($key))
                ->placeholder($options['placeholder'] ?? "$placeholderPrefix $key")
                ->required($options['required'] ?? false);

            if ($options['password'] ?? false) {
                $field->password()->revealable();
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public static function getCarrierOptionsForApiType(?string $apiType): array
    {
        $type = strtolower(trim((string) $apiType));

        if ($type === 'mno') {
            return self::getMnoCarrierOptions();
        }

        return self::getIptspCarrierOptions();
    }

    public static function getIptspCarrierOptions(): array
    {
        return [
            'ADN' => 'ADN',
            'MN' => 'Mirnet',
            'BN' => 'Bracnet',
            'AIT' => 'AmberIT',
            'RT' => 'RanksITT',
            'PRM' => 'Premium',
            'FN' => 'FusionNet',
            'BR' => 'Brilliant',
            'MTN' => 'Metronet',
            'RCO' => 'RaceOnline',
            'BPL' => 'BanglaPhone',
            'ICN' => 'Icon',
            'WEB' => 'Weblink',
            'BDC' => 'BDCOM',
            'RD' => 'RedData',
            'L3' => 'Link3',
            'BTCL' => 'BTCL',
            'AGNI' => 'AGNI',
            'ICC' => 'ICC',
            'RG' => 'RoyalGreen',
        ];
    }

    public static function getMnoCarrierOptions(): array
    {
        return [
            'GP' => 'Grameenphone',
            'BL' => 'Banglalink',
            'RB' => 'Robi',
            'TT' => 'Teletalk',
        ];
    }

    public static function getDriverOptions(): Collection
    {
        return collect(config('sms.drivers'))
            ->mapWithKeys(fn ($value, $key) => [$key => $value['title']]);
    }

    /**
     * Pre-seed the credentials and config arrays with all expected keys
     * for the selected driver, so Livewire's server-side $data contains
     * the property paths before Alpine's entangle bindings fire.
     */
    private static function seedDriverState(Set $set, string $driver): void
    {
        $driverConfig = config("sms.drivers.$driver");

        if (blank($driverConfig)) {
            return;
        }

        foreach (['credentials', 'config'] as $section) {
            $sectionConfig = data_get($driverConfig, $section, []);

            foreach ($sectionConfig as $key => $options) {
                $type = $options['type'] ?? 'text';

                if ($type === 'hidden') {
                    continue;
                }

                $default = match ($type) {
                    'multi_select' => [],
                    'select' => $options['default'] ?? null,
                    default => $options['default'] ?? null,
                };

                $set("$section.$key", $default);
            }
        }
    }
}
