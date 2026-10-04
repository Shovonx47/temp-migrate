<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Schemas;

use App\Enums\GatewayCostMode;
use App\Enums\GatewayDriver;
use App\Models\Gateway;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Livewire\Component;

class GatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::gatewayDetailsSection(),
            self::billingConfigurationSection(),
            self::configurationSection(),
        ]);
    }

    private static function billingConfigurationSection(): Section
    {
        return Section::make('Billing & Cost Configuration')
            ->description('Configure DLR tracking, dipping rules, and cost modes for this gateway.')
            ->schema([
                Select::make('dlr_support')
                    ->label('DLR Support')
                    ->boolean()
                    ->default(true)
                    ->helperText('Determines whether delivery reports are tracked or explicitly marked N/A in analytics.')
                    ->required(),
                Select::make('cost_mode')
                    ->label('Cost Mode')
                    ->options(GatewayCostMode::class)
                    ->default(GatewayCostMode::Manual->value)
                    ->helperText('Manual is the authoritative billing source. API assisted allows cross-check.')
                    ->required(),
                Select::make('dipping_required')
                    ->label('Mandatory Dipping')
                    ->boolean()
                    ->default(true)
                    ->helperText('If enabled, profit calculation will require a configured dipping charge and refuse silent zeroing.')
                    ->required(),
                Select::make('has_third_party_dipping')
                    ->label('Third-Party Dipping')
                    ->boolean()
                    ->default(false)
                    ->helperText('Enables third-party dipping volume and cost tracking on this gateway.')
                    ->required(),
            ])
            ->columns(2);
    }

    private static function gatewayDetailsSection(): Section
    {
        return Section::make('Gateway Details')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('driver')
                    ->options(fn (?Gateway $record): Collection => self::getDriverOptions($record?->driver?->value))
                    ->default(GatewayDriver::Infozillion->value)
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
                TextInput::make('requests_per_second')
                    ->label('Requests Per Second')
                    ->numeric()
                    ->minValue(1)
                    ->default(60)
                    ->required(),
                TextInput::make('timeout_seconds')
                    ->label('Connection Timeout (Seconds)')
                    ->helperText('Maximum HTTP request timeout before failing over to the next provider.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120)
                    ->default(2)
                    ->required(),
                TextInput::make('priority')
                    ->label('OTP Gateway Priority')
                    ->helperText('Lower number = higher priority during OTP failover (e.g. 1 is tried first).')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Select::make('is_otp_enabled')
                    ->label('Use for OTP Sending')
                    ->boolean()
                    ->default(true)
                    ->helperText('Include this gateway in OTP priority delivery failover.')
                    ->required(),
                Select::make('is_enabled')
                    ->label('Gateway Enabled')
                    ->boolean()
                    ->default(true)
                    ->required(),
                Select::make('supports_bulk')
                    ->label('Supports Promotional')
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

            if ($type === 'tags' || $type === 'multi_select') {
                $fields[] = TagsInput::make("$section.$key")
                    ->label($options['label'] ?? ucfirst($key))
                    ->placeholder($options['placeholder'] ?? "$placeholderPrefix $key")
                    ->helperText($options['helper'] ?? null)
                    ->required($options['required'] ?? false);

                continue;
            }

            if ($type === 'select') {
                $field = Select::make("$section.$key")
                    ->label($options['label'] ?? ucfirst($key))
                    ->options($options['options'] ?? [])
                    ->live()
                    ->afterStateUpdated(function (?string $state, Component $livewire) use ($key): void {
                        if ($key === 'api_type') {
                            $livewire->dispatch('gateway-api-type-changed', apiType: $state);
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

            if (isset($options['default'])) {
                $field->default($options['default']);
            }

            if ($options['password'] ?? false) {
                $field->password()->revealable();
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public static function getDriverOptions(?string $currentDriver = null): Collection
    {
        $keys = [GatewayDriver::Infozillion->value];

        if (filled($currentDriver) && $currentDriver !== GatewayDriver::Infozillion->value && filled(config("sms.drivers.$currentDriver"))) {
            $keys[] = $currentDriver;
        }

        return collect(config('sms.drivers'))
            ->only($keys)
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
                    'tags', 'multi_select' => [],
                    'select' => $options['default'] ?? null,
                    default => $options['default'] ?? null,
                };

                $set("$section.$key", $default);
            }
        }
    }
}
