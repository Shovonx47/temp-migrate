<?php

namespace App\Filament\Imports;

use App\Enums\SenderType;
use App\Models\Gateway;
use App\Models\Sender;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;
use Illuminate\Support\Number;

class SenderImporter extends Importer
{
    protected static ?string $model = Sender::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('sender_id')
                ->label('Sender ID (CLI)')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('gateway')
                ->label('Gateway')
                ->relationship(resolveUsing: function (string $state): ?Gateway {
                    return Gateway::query()
                        ->where(function ($query) use ($state): void {
                            $query->where('name', $state)
                                ->orWhere('id', $state);
                        })
                        ->orWhere(function ($query) use ($state): void {
                            $query->whereNotNull('credentials')
                                ->whereJsonContains('credentials->bill_msisdn', $state);
                        })
                        ->first();
                }),

            ImportColumn::make('type')
                ->label('Type')
                ->castStateUsing(function ($state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    $state = strtolower(trim((string) $state));

                    return match ($state) {
                        'numeric', 'number', 'non-masking', 'non_masking' => SenderType::Numeric->value,
                        'alphanumeric', 'alpha_numeric', 'masking', 'alpha' => SenderType::AlphaNumeric->value,
                        'shortcode', 'short_code' => SenderType::Shortcode->value,
                        default => $state,
                    };
                })
                ->rules(['nullable', 'string']),

            ImportColumn::make('is_enabled')
                ->label('Enabled')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('default_gateway_id')
                ->label('Default Gateway (if not in file)')
                ->options(fn () => Gateway::pluck('name', 'id')->all())
                ->searchable()
                ->preload(),

            Select::make('default_type')
                ->label('Default Sender Type (if not in file)')
                ->options(SenderType::maskingCategoryOptions())
                ->default(SenderType::MaskingCategory),

            Select::make('assigned_customers')
                ->label('Assign Imported Senders to Customers')
                ->multiple()
                ->options(fn () => User::where('type', 'customer')->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
        ];
    }

    public function resolveRecord(): ?Sender
    {
        $senderId = trim((string) ($this->data['sender_id'] ?? ''));
        $gatewayId = $this->record?->gateway_id
            ?? $this->getOptions()['default_gateway_id']
            ?? null;

        if ($senderId === '') {
            return null;
        }

        $sender = Sender::withTrashed()
            ->where('sender_id', $senderId)
            ->when($gatewayId, fn ($q) => $q->where('gateway_id', $gatewayId))
            ->first();

        if ($sender) {
            if ($sender->trashed()) {
                $sender->restore();
            }

            return $sender;
        }

        $type = SenderType::normalizeStoredValue($this->data['type'] ?? null);

        if (! $type) {
            $type = SenderType::normalizeStoredValue($this->getOptions()['default_type'] ?? null);
        }

        if (! $type) {
            $type = SenderType::AlphaNumeric->value;
        }

        $sender = new Sender;
        $sender->gateway_id = $gatewayId;
        $sender->type = $type;
        $sender->is_enabled = $this->data['is_enabled'] ?? true;

        return $sender;
    }

    protected function afterSave(): void
    {
        $customerIds = $this->getOptions()['assigned_customers'] ?? [];

        if (! empty($customerIds)) {
            $this->record->users()->syncWithoutDetaching($customerIds);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = sprintf(
            'Your sender import has completed and %s %s imported.',
            Number::format($import->successful_rows),
            str('row')->plural($import->successful_rows)
        );

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= sprintf(
                ' %s %s failed to import.',
                Number::format($failedRowsCount),
                str('row')->plural($failedRowsCount)
            );
        }

        return $body;
    }
}
