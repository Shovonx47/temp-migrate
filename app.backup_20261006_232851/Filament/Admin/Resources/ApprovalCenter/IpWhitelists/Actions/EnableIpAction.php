<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions;

use App\Enums\IpWhitelistStatus;
use App\Models\IpWhitelistAuditLog;
use App\Models\IpWhitelistRequest;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class EnableIpAction
{
    public static function make(?string $name = 'enable_ip'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedArrowPath)
            ->label('Enable')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Re-activate IP Whitelist Entry')
            ->modalDescription(fn (IpWhitelistRequest $record) => "Are you sure you want to re-enable whitelist access for IP {$record->ip_address} (Customer: {$record->user?->name})?")
            ->visible(fn (IpWhitelistRequest $record) => in_array($record->status, [IpWhitelistStatus::Disabled, IpWhitelistStatus::Rejected], true))
            ->action(function (IpWhitelistRequest $record) {
                $oldStatus = $record->status;

                $record->update([
                    'status'      => IpWhitelistStatus::Approved,
                    'reviewed_by' => Filament::auth()->id(),
                    'reviewed_at' => now(),
                    'admin_note'  => null,
                ]);

                // Audit Log
                IpWhitelistAuditLog::record(
                    userId: $record->user_id,
                    action: 'enabled',
                    ipAddress: $record->ip_address,
                    requestId: $record->id,
                    oldValues: ['status' => $oldStatus?->value],
                    newValues: ['status' => IpWhitelistStatus::Approved->value],
                    note: 'Re-enabled / re-activated by admin',
                );

                if ($record->user) {
                    Notification::make()
                        ->title('IP Whitelist Re-activated')
                        ->body("Your IP whitelist entry for {$record->ip_address} has been re-activated and approved.")
                        ->success()
                        ->sendToDatabase($record->user);
                }

                Notification::make()
                    ->title('IP Whitelist Re-activated')
                    ->body("IP {$record->ip_address} for customer {$record->user?->name} is now approved and active.")
                    ->success()
                    ->send();
            });
    }
}
