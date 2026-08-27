<?php

namespace App\Filament\Admin\Pages\Providers;

use App\Enums\SupermanNavigationGroup;
use App\Models\Gateway;
use App\Services\Sms\GatewayHealthService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class ProviderHealthPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Providers;

    protected static ?string $navigationLabel = 'Provider Health';

    protected static ?string $title = 'Provider & Gateway Health Monitor';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'providers/health';

    protected string $view = 'filament.admin.pages.providers.provider-health-page';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check_all')
                ->label('Check All Providers')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->action(function (GatewayHealthService $healthService) {
                    $results = $healthService->checkAll();
                    $healthyCount = collect($results)->where('status', 'healthy')->count();
                    $total = count($results);

                    Notification::make()
                        ->title('Health Check Completed')
                        ->body("{$healthyCount} / {$total} providers are currently healthy and operational.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Gateway::query()->orderBy('priority', 'asc'))
            ->columns([
                TextColumn::make('name')
                    ->label('Provider / Gateway')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('driver')
                    ->label('Driver')
                    ->badge()
                    ->color('info'),

                TextColumn::make('health_status')
                    ->label('Health')
                    ->badge()
                    ->state(fn (Gateway $record): string => strtoupper($record->health_status ?? 'UNKNOWN'))
                    ->color(fn (Gateway $record): string => match ($record->health_status) {
                        'healthy' => 'success',
                        'degraded' => 'warning',
                        'down' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('last_health_latency_ms')
                    ->label('Latency')
                    ->state(fn (Gateway $record): string => $record->last_health_latency_ms ? "{$record->last_health_latency_ms} ms" : '—')
                    ->badge()
                    ->color(fn (Gateway $record): string => match (true) {
                        ! $record->last_health_latency_ms => 'gray',
                        $record->last_health_latency_ms < 500 => 'success',
                        $record->last_health_latency_ms < 2000 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('balance')
                    ->label('Provider Balance')
                    ->state(function (Gateway $record): string {
                        $resp = $record->last_health_response;

                        return $resp['balance'] ?? '—';
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('timeout_seconds')
                    ->label('Timeout')
                    ->formatStateUsing(fn ($state) => "{$state}s")
                    ->alignCenter(),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn ($state) => "Priority {$state}")
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('last_health_check_at')
                    ->label('Last Checked')
                    ->dateTime('M d, H:i:s')
                    ->placeholder('Never'),

                TextColumn::make('is_enabled')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Gateway $record): string => $record->is_enabled ? 'ENABLED' : 'DISABLED')
                    ->color(fn (Gateway $record): string => $record->is_enabled ? 'success' : 'danger'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('check_health')
                    ->label('Ping / Test')
                    ->icon(Heroicon::OutlinedBolt)
                    ->color('info')
                    ->action(function (Gateway $record, GatewayHealthService $healthService) {
                        $res = $healthService->checkGateway($record);

                        $notif = Notification::make()
                            ->title("Health Check for {$record->name}")
                            ->body($res['message']);

                        if ($res['status'] === 'healthy') {
                            $notif->success();
                        } elseif ($res['status'] === 'degraded') {
                            $notif->warning();
                        } else {
                            $notif->danger();
                        }

                        $notif->send();
                    }),
            ]);
    }
}
