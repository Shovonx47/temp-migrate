<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\AuditLogs;

use App\Enums\SupermanNavigationGroup;
use App\Models\IpWhitelistAuditLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class IpWhitelistAuditPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::AuditLogs;

    protected static ?string $navigationLabel = 'IP Whitelist Audit';

    protected static ?string $title = 'IP Whitelist Audit Log';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'audit-logs/ip-whitelist';

    protected string $view = 'filament.admin.pages.audit-logs.ip-whitelist-audit';

    public function table(Table $table): Table
    {
        return $table
            ->query(IpWhitelistAuditLog::query()->with(['user', 'actor'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y, h:i:s A')
                    ->sortable(),

                TextColumn::make('actor_name')
                    ->label('Actor')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (IpWhitelistAuditLog $record) => ucfirst($record->actor_type)),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn (IpWhitelistAuditLog $record) => $record->user?->email)
                    ->url(fn (IpWhitelistAuditLog $record): ?string => $record->user_id ? url("/superman/users/customers/{$record->user_id}") : null),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved', 'enabled' => 'success',
                        'rejected', 'disabled' => 'danger',
                        'updated'              => 'warning',
                        'created'              => 'info',
                        'deleted'              => 'gray',
                        default                => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('ip_address')
                    ->label('IP / CIDR')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('note')
                    ->label('Note / Reason')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn (IpWhitelistAuditLog $record) => $record->note),

                TextColumn::make('source_ip')
                    ->label('Origin IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'created'  => 'Created (Requested)',
                        'updated'  => 'Updated (Edited)',
                        'approved' => 'Approved',
                        'disabled' => 'Disabled',
                        'enabled'  => 'Enabled',
                        'rejected' => 'Rejected',
                        'deleted'  => 'Deleted',
                    ]),

                SelectFilter::make('actor_type')
                    ->label('Actor Type')
                    ->options([
                        'admin'    => 'Admin',
                        'customer' => 'Customer',
                        'system'   => 'System',
                    ]),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalWidth(Width::Medium)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalHeading(fn (IpWhitelistAuditLog $record) => "Audit Details: " . ucfirst($record->action) . " {$record->ip_address}")
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('actor_name')
                            ->label('Performed By')
                            ->state(fn (IpWhitelistAuditLog $record) => "{$record->actor_name} (" . ucfirst($record->actor_type) . ")"),
                        \Filament\Infolists\Components\TextEntry::make('source_ip')
                            ->label('Originating IP Address')
                            ->placeholder('Unknown'),
                        \Filament\Infolists\Components\TextEntry::make('note')
                            ->label('Note / Reason')
                            ->placeholder('No remarks recorded'),
                        \Filament\Infolists\Components\KeyValueEntry::make('old_values')
                            ->label('Old Values')
                            ->visible(fn (IpWhitelistAuditLog $record) => ! empty($record->old_values)),
                        \Filament\Infolists\Components\KeyValueEntry::make('new_values')
                            ->label('New Values')
                            ->visible(fn (IpWhitelistAuditLog $record) => ! empty($record->new_values)),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
