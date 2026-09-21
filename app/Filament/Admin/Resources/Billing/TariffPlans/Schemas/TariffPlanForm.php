<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\Schemas;

use App\Enums\TariffPlanStatus;
use App\Enums\TariffPlanType;
use App\Filament\Forms\Components\SignupSlugInput;
use App\Services\TariffSlugService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TariffPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Plan Details')
                    ->description('Define the name, classification, and status of this tariff plan.')
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. 35 Paisa Standard Plan')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            // On create only: suggest a slug from the name if none was entered yet.
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                                if ($operation === 'create' && blank($get('slug')) && filled($state)) {
                                    $set('slug', TariffSlugService::generate($state));
                                }
                            }),
                        SignupSlugInput::make('slug'),
                        Select::make('type')
                            ->options(TariffPlanType::class)
                            ->default(TariffPlanType::Standard)
                            ->required()
                            ->helperText('Standard plans apply to most customers. Custom plans are for negotiated pricing.'),
                        Select::make('status')
                            ->options(TariffPlanStatus::class)
                            ->default(TariffPlanStatus::Draft)
                            ->required()
                            ->helperText('Only Active plans can be assigned to customers.'),
                        TextInput::make('currency')
                            ->default('BDT')
                            ->required()
                            ->maxLength(10)
                            ->helperText('Informational label only (e.g. BDT, USD).'),
                        Textarea::make('description')
                            ->placeholder('Optional notes about this plan…')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
