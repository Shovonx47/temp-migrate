<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists\Tables;

use App\Enums\IpWhitelistStatus;
use App\Filament\Customer\Resources\Developer\IpWhitelists\Schemas\IpWhitelistForm;
use App\Models\IpWhitelistAuditLog;
use App\Models\IpWhitelistRequest;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IpWhitelistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
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
                    ->color('danger')
                    ->limit(50)
                    ->tooltip(fn (IpWhitelistRequest $record): ?string => $record->admin_note),

                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit')
                        ->modalWidth(Width::Medium)
                        ->schema(fn ($schema) => IpWhitelistForm::configure($schema)->getComponents())
                        ->mutateFormDataUsing(function (array $data, IpWhitelistRequest $record): array {
                            // If the customer changed the IP address itself, reset status to Pending
                            if (trim($data['ip_address']) !== trim($record->ip_address)) {
                                $data['status'] = IpWhitelistStatus::Pending;
                                $data['admin_note'] = null;
                            }
                            return $data;
                        })
                        ->after(function (IpWhitelistRequest $record, array $data): void {
                            $oldValues = [
                                'ip_address' => $record->getOriginal('ip_address'),
                                'label'      => $record->getOriginal('label'),
                                'status'     => $record->getOriginal('status') instanceof IpWhitelistStatus
                                    ? $record->getOriginal('status')->value
                                    : (string) $record->getOriginal('status'),
                            ];

                            $newValues = [
                                'ip_address' => $record->ip_address,
                                'label'      => $record->label,
                                'status'     => $record->status->value,
                            ];

                            $ipChanged = $oldValues['ip_address'] !== $newValues['ip_address'];

                            IpWhitelistAuditLog::record(
                                userId: $record->user_id,
                                action: 'updated',
                                ipAddress: $record->ip_address,
                                requestId: $record->id,
                                oldValues: $oldValues,
                                newValues: $newValues,
                                note: $ipChanged ? 'Customer edited IP address (status reset to pending review)' : 'Customer edited label',
                            );

                            if ($ipChanged) {
                                Notification::make()
                                    ->title('IP Address Updated')
                                    ->body('Because the IP address was modified, it has been resubmitted for admin review.')
                                    ->warning()
                                    ->send();
                            }
                        }),

                    DeleteAction::make()
                        ->label('Remove')
                        ->modalHeading('Remove IP Whitelist Entry')
                        ->modalDescription('Are you sure you want to remove this IP address? If approved, API requests from this IP will no longer be authorized.')
                        ->before(function (IpWhitelistRequest $record) {
                            IpWhitelistAuditLog::record(
                                userId: $record->user_id,
                                action: 'deleted',
                                ipAddress: $record->ip_address,
                                requestId: $record->id,
                                oldValues: [
                                    'ip_address' => $record->ip_address,
                                    'label'      => $record->label,
                                    'status'     => $record->status?->value,
                                ],
                                newValues: null,
                                note: 'Deleted by customer',
                            );
                        }),
                ]),
            ])
            ->toolbarActions([]);
    }
}
