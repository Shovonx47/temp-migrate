<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions;

use App\Enums\IpWhitelistStatus;
use App\Models\IpWhitelistAuditLog;
use App\Models\IpWhitelistRequest;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class RevokeIpAction
{
    public static function make(?string $name = 'revoke_ip'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedNoSymbol)
            ->label('Disable')
            ->color('warning')
            ->modalHeading('Disable IP Whitelist Entry')
            ->modalDescription('Are you sure you want to disable whitelist access for this IP? The customer will immediately be blocked from calling the API from this IP.')
            ->modalWidth(Width::Medium)
            ->visible(fn (IpWhitelistRequest $record) => $record->status === IpWhitelistStatus::Approved)
            ->schema([
                Textarea::make('admin_note')
                    ->label('Reason for Disabling (Optional)')
                    ->placeholder('e.g. Access disabled due to security audit / policy violation.')
                    ->rows(3),
            ])
            ->action(function (array $data, IpWhitelistRequest $record) {
                $oldStatus = $record->status;
                $note = ! empty($data['admin_note']) ? $data['admin_note'] : 'Whitelist access was disabled by administrator.';

                $record->update([
                    'status'      => IpWhitelistStatus::Disabled,
                    'admin_note'  => $note,
                    'reviewed_by' => Filament::auth()->id(),
                    'reviewed_at' => now(),
                ]);

                // Audit Log
                IpWhitelistAuditLog::record(
                    userId: $record->user_id,
                    action: 'disabled',
                    ipAddress: $record->ip_address,
                    requestId: $record->id,
                    oldValues: ['status' => $oldStatus?->value],
                    newValues: ['status' => IpWhitelistStatus::Disabled->value],
                    note: 'Disabled by admin. Reason: '.$note,
                );

                if ($record->user) {
                    Notification::make()
                        ->title('IP Whitelist Disabled')
                        ->body("Your whitelisted IP {$record->ip_address} has been disabled by admin. Reason: {$note}")
                        ->warning()
                        ->sendToDatabase($record->user);
                }

                Notification::make()
                    ->title('IP Whitelist Disabled')
                    ->body("IP {$record->ip_address} for customer {$record->user?->name} has been disabled.")
                    ->warning()
                    ->send();
            });
    }
}
