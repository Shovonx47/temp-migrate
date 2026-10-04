<?php

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Tables;

use App\Enums\IpWhitelistStatus;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\ApproveIpAction;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\EnableIpAction;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\RejectIpAction;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\RevokeIpAction;
use App\Filament\Customer\Resources\Developer\IpWhitelists\Schemas\IpWhitelistForm;
use App\Models\IpWhitelistAuditLog;
use App\Models\IpWhitelistRequest;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IpWhitelistRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (IpWhitelistRequest $record): ?string => $record->user_id ? url("/superman/users/customers/{$record->user_id}") : null),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address / CIDR')
                    ->searchable()
                    ->copyable()
                    ->weight('medium')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Label')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('admin_note')
                    ->label('Admin Remark')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn (IpWhitelistRequest $record): ?string => $record->admin_note)
                    ->toggleable(),

                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(IpWhitelistStatus::class)
                    ->default(IpWhitelistStatus::Pending->value),
            ])
            ->recordActions([
                ActionGroup::make([
                    ApproveIpAction::make(),
                    EnableIpAction::make(),
                    RevokeIpAction::make(),
                    RejectIpAction::make(),

                    EditAction::make('admin_edit')
                        ->label('Edit Entry')
                        ->icon(Heroicon::OutlinedPencil)
                        ->modalWidth(Width::Medium)
                        ->schema([
                            TextInput::make('ip_address')
                                ->label('Public IP Address / CIDR')
                                ->required()
                                ->maxLength(45),
                            TextInput::make('label')
                                ->label('Label / Description')
                                ->maxLength(100),
                            Select::make('status')
                                ->label('Status')
                                ->options(IpWhitelistStatus::class)
                                ->required(),
                            Textarea::make('admin_note')
                                ->label('Admin Remark / Reason')
                                ->rows(3),
                        ])
                        ->after(function (IpWhitelistRequest $record, array $data): void {
                            $oldValues = [
                                'ip_address' => $record->getOriginal('ip_address'),
                                'label'      => $record->getOriginal('label'),
                                'status'     => $record->getOriginal('status') instanceof IpWhitelistStatus
                                    ? $record->getOriginal('status')->value
                                    : (string) $record->getOriginal('status'),
                                'admin_note' => $record->getOriginal('admin_note'),
                            ];

                            $newValues = [
                                'ip_address' => $record->ip_address,
                                'label'      => $record->label,
                                'status'     => $record->status->value,
                                'admin_note' => $record->admin_note,
                            ];

                            IpWhitelistAuditLog::record(
                                userId: $record->user_id,
                                action: 'updated',
                                ipAddress: $record->ip_address,
                                requestId: $record->id,
                                oldValues: $oldValues,
                                newValues: $newValues,
                                note: 'Edited by admin',
                            );
                        }),

                    Action::make('view_audit_history')
                        ->label('Audit History')
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->color('gray')
                        ->modalHeading(fn (IpWhitelistRequest $record) => "Audit History: {$record->ip_address} ({$record->user?->name})")
                        ->modalWidth(Width::Large)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->infolist([
                            \Filament\Infolists\Components\RepeatableEntry::make('auditLogs')
                                ->label('Event Log')
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('action')
                                        ->badge()
                                        ->color(fn (string $state) => match ($state) {
                                            'approved', 'enabled' => 'success',
                                            'rejected', 'disabled' => 'danger',
                                            'updated'              => 'warning',
                                            'created'              => 'info',
                                            default                => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state) => ucfirst($state)),
                                    \Filament\Infolists\Components\TextEntry::make('actor_name')
                                        ->label('Actor')
                                        ->formatStateUsing(fn ($state, IpWhitelistAuditLog $r) => "{$state} (" . ucfirst($r->actor_type) . ")"),
                                    \Filament\Infolists\Components\TextEntry::make('note')
                                        ->label('Note / Reason')
                                        ->placeholder('—'),
                                    \Filament\Infolists\Components\TextEntry::make('created_at')
                                        ->label('Timestamp')
                                        ->dateTime('M d, Y h:i:s A'),
                                ])
                                ->columns(4),
                        ]),
                ]),
            ])
            ->toolbarActions([]);
    }
}
