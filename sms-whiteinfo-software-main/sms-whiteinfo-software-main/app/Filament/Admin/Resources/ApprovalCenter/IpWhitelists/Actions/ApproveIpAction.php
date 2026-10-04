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

class ApproveIpAction
{
    public static function make(?string $name = 'approve_ip'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedCheckCircle)
            ->label('Approve')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve IP Whitelist Request')
            ->modalDescription(fn (IpWhitelistRequest $record) => "Are you sure you want to approve IP {$record->ip_address} for customer {$record->user?->name}?")
            ->visible(fn (IpWhitelistRequest $record) => $record->status === IpWhitelistStatus::Pending)
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
                    action: 'approved',
                    ipAddress: $record->ip_address,
                    requestId: $record->id,
                    oldValues: ['status' => $oldStatus?->value],
                    newValues: ['status' => IpWhitelistStatus::Approved->value],
                    note: 'Approved by admin',
                );

                if ($record->user) {
                    Notification::make()
                        ->title('IP Whitelist Approved')
                        ->body("Your IP whitelist request for {$record->ip_address} has been approved.")
                        ->success()
                        ->sendToDatabase($record->user);
                }

                Notification::make()
                    ->title('IP Whitelist Approved')
                    ->body("IP {$record->ip_address} for customer {$record->user?->name} has been approved.")
                    ->success()
                    ->send();
            });
    }
}
