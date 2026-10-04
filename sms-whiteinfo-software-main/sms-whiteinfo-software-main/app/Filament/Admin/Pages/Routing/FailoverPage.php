<?php

namespace App\Filament\Admin\Pages\Routing;

use App\Enums\SupermanNavigationGroup;
use App\Models\SmsLog;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class FailoverPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Routing;

    protected static ?string $navigationLabel = 'Failover Audit';

    protected static ?string $title = 'Automatic Failover Monitoring & Logs';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'routing/failover';

    protected string $view = 'filament.admin.pages.routing.failover-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SmsLog::query()
                    ->whereIn('event', ['sms_gateway_failover', 'sms_gateway_failover_exception', 'sms_send_failed_all_gateways'])
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Recipient')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('gateway_name')
                    ->label('Failing Provider')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('event')
                    ->label('Failover Event')
                    ->badge()
                    ->state(fn (SmsLog $log) => match ($log->event) {
                        'sms_gateway_failover' => 'TRIGGERED FAILOVER',
                        'sms_gateway_failover_exception' => 'TIMEOUT / CONNECTION ERROR',
                        default => 'ALL GATEWAYS EXHAUSTED',
                    })
                    ->color(fn (SmsLog $log) => match ($log->event) {
                        'sms_send_failed_all_gateways' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('error_message')
                    ->label('Error Detail / Reason')
                    ->wrap(),
            ]);
    }
}
