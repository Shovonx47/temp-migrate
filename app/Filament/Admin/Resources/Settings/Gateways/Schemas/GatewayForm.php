<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->options(fn (Get $get) => self::getCarrierOptions($get("{$section}.api_type") ?? 'mno'))
                    ->live()
                    ->required($options['required'] ?? false);

                continue;
            }

            if ($type === 'select') {
                $fields[] = Select::make("$section.$key")
                    ->label($options['label'] ?? ucfirst($key))
                    ->options($options['options'] ?? [])
                    ->required($options['required'] ?? false);

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

    private static function getCarrierOptions(string $apiType): array
    {
        return match ($apiType) {
            'iptsp' => [
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
            ],
            default => [
                'GP' => 'GrameenPhone',
                'RB' => 'Robi',
                'TT' => 'Teletalk',
                'BL' => 'Banglalink',
            ],
        };
    }

    public static function getDriverOptions(): Collection
    {
        return collect(config('sms.drivers'))
            ->mapWithKeys(fn ($value, $key) => [$key => $value['title']]);
    }
}
