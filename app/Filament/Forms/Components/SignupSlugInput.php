<?php

namespace App\Filament\Forms\Components;

use App\Services\TariffSlugService;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SignupSlugInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Signup URL Slug')
            ->placeholder('e.g. premium-plan')
            ->maxLength(TariffSlugService::MAX_LENGTH)
            // Optional on create (the model generates one when blank), required on edit.
            ->required(fn (string $operation): bool => $operation === 'edit')
            ->regex(TariffSlugService::PATTERN)
            // Global uniqueness across ALL plans (admin and every reseller). Raw table query,
            // so it is not limited by ScopedBySp.
            ->unique(table: 'tariff_plans', column: 'slug', ignoreRecord: true)
            // ...and against reserved/old slugs, except this plan's own (so it can revert).
            ->rules([
                fn (?Model $record = null): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    $recordId = $record?->getKey() ?? $this->getRecord()?->getKey();
                    if (filled($value) && TariffSlugService::isAliasTaken((string) $value, $recordId)) {
                        $fail('This slug is already reserved by another plan.');
                    }
                },
            ])
            ->validationMessages([
                'regex' => 'Use lowercase letters, numbers, and single hyphens only (e.g. premium-plan).',
                'unique' => 'This slug is already used by another plan.',
            ])
            ->live(onBlur: true)
            // Tidy whatever was typed ("Premium Plan" -> "premium-plan").
            ->afterStateUpdated(function (Set $set, ?string $state): void {
                if (filled($state)) {
                    $set('slug', Str::slug($state));
                }
            })
            ->helperText(fn (?string $state): string => 'Signup link: '.url('/register/'.(filled($state) ? $state : '{slug}')).'. The link only works while the plan is Active.');
    }
}
