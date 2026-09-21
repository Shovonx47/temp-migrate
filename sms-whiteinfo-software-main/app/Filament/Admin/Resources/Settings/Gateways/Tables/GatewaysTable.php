<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Tables;

use App\Models\Gateway;
use App\Services\Analytics\GatewayMetricsService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GatewaysTable
{
    public static function configure(Table $table): Table
    {
        $metricsService = app(GatewayMetricsService::class);

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Gateway')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(false),

                TextColumn::make('route_code')
                    ->label('Route Code')
                    ->state(fn (Gateway $record): ?string => $record->route_code)
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->wrap(false),

                TextColumn::make('priority')
                    ->label('OTP Priority')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => "Priority {$state}")
                    ->sortable(),

                TextColumn::make('is_otp_enabled')
                    ->label('OTP Route')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'ENABLED' : 'DISABLED')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('is_enabled')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record): string => $record->is_enabled ? 'ACTIVE' : 'INACTIVE')
                    ->color(fn ($record): string => $record->is_enabled ? 'success' : 'danger')
                    ->wrap(false),

                TextColumn::make('requests_per_second')
                    ->label('RPS')
                    ->sortable()
                    ->alignCenter()
                    ->wrap(false),

                TextColumn::make('cost_mode')
                    ->label('Cost Mode')
                    ->state(fn ($record): string => ucfirst($record->cost_mode?->value ?? 'manual'))
                    ->badge()
                    ->color('gray')
                    ->wrap(false),

                TextColumn::make('dlr_support')
                    ->label('DLR Support')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'YES' : 'NO')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->alignCenter()
                    ->wrap(false),

                TextColumn::make('masking_sent')
                    ->label('Masking Sent')
                    ->state(fn ($record): string => number_format((int) ($metricsService->getMetrics($record)['masking_sent'] ?? 0)))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('non_masking_sent')
                    ->label('Non-Masking Sent')
                    ->state(fn ($record): string => number_format((int) ($metricsService->getMetrics($record)['non_masking_sent'] ?? 0)))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('total_sent')
                    ->label('Total Sent')
                    ->state(fn ($record): string => number_format((int) ($metricsService->getMetrics($record)['total_sent'] ?? 0)))
                    ->weight('bold')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('masking_delivered')
                    ->label('Masking Delivered')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['masking_delivered'];

                        return $val !== null ? number_format((int) $val) : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('non_masking_delivered')
                    ->label('Non-Masking Delivered')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['non_masking_delivered'];

                        return $val !== null ? number_format((int) $val) : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('total_delivered')
                    ->label('Total Delivered')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['total_delivered'];

                        return $val !== null ? number_format((int) $val) : 'N/A';
                    })
                    ->weight('bold')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('dlr_pending')
                    ->label('DLR Pending')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['dlr_pending'];

                        return $val !== null ? number_format((int) $val) : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('dlr_pending_pct')
                    ->label('DLR Pending %')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['dlr_pending_pct'];

                        return $val !== null ? number_format((float) $val, 2).'%' : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('send_failed')
                    ->label('Send Failed')
                    ->state(fn ($record): string => number_format((int) ($metricsService->getMetrics($record)['send_failed'] ?? 0)))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('send_failed_pct')
                    ->label('Send Failed %')
                    ->state(fn ($record): string => number_format((float) ($metricsService->getMetrics($record)['send_failed_pct'] ?? 0), 2).'%')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('dlr_failed')
                    ->label('DLR Failed')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['dlr_failed'];

                        return $val !== null ? number_format((int) $val) : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('dlr_failed_pct')
                    ->label('DLR Failed %')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['dlr_failed_pct'];

                        return $val !== null ? number_format((float) $val, 2).'%' : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('processing')
                    ->label('Processing')
                    ->state(fn ($record): string => number_format((int) ($metricsService->getMetrics($record)['processing'] ?? 0)))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('processing_pct')
                    ->label('Processing %')
                    ->state(fn ($record): string => number_format((float) ($metricsService->getMetrics($record)['processing_pct'] ?? 0), 2).'%')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('masking_delivery_pct')
                    ->label('Masking Delivery %')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['masking_delivery_pct'];

                        return $val !== null ? number_format((float) $val, 2).'%' : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('non_masking_delivery_pct')
                    ->label('Non-Masking Delivery %')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['non_masking_delivery_pct'];

                        return $val !== null ? number_format((float) $val, 2).'%' : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('masking_cost')
                    ->label('Masking Cost')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['masking_cost'] ?? 0), 4))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('non_masking_cost')
                    ->label('Non-Masking Cost')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['non_masking_cost'] ?? 0), 4))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('operator_cost')
                    ->label('Operator Cost')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['operator_cost'] ?? 0), 2))
                    ->badge()
                    ->color('warning')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('dipping_charge')
                    ->label('Dipping Charge')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['dipping_charge'] ?? 0), 2))
                    ->badge()
                    ->color('info')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('third_party_dipping_cost')
                    ->label('Third-Party Dipping Cost')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['third_party_dipping_cost'] ?? 0), 2))
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['total_cost'] ?? 0), 2))
                    ->badge()
                    ->color('danger')
                    ->weight('bold')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('provider_balance')
                    ->label('Provider Balance')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['provider_balance'];

                        return $val !== null ? (is_numeric($val) ? '৳'.number_format((float) $val, 2) : (string) $val) : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('dipping_balance')
                    ->label('Dipping Balance')
                    ->state(function ($record) use ($metricsService): string {
                        $val = $metricsService->getMetrics($record)['dipping_balance'];

                        return $val !== null ? (is_numeric($val) ? '৳'.number_format((float) $val, 2) : (string) $val) : 'N/A';
                    })
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['revenue'] ?? 0), 2))
                    ->badge()
                    ->color('success')
                    ->weight('bold')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('profit')
                    ->label('Profit')
                    ->state(fn ($record): string => '৳'.number_format((float) ($metricsService->getMetrics($record)['profit'] ?? 0), 2))
                    ->badge()
                    ->color(fn ($record): string => ($metricsService->getMetrics($record)['profit'] ?? 0) >= 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->alignEnd()
                    ->wrap(false),

                TextColumn::make('verification')
                    ->label('Verification')
                    ->badge()
                    ->state(fn ($record): string => $metricsService->getMetrics($record)['verification'])
                    ->color(fn ($record): string => $metricsService->getMetrics($record)['verification_color'])
                    ->wrap(false),

                TextColumn::make('driver')
                    ->label('Driver')
                    ->badge()
                    ->wrap(false),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
