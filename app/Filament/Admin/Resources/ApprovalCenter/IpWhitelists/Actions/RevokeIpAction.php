<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions;

use App\Enums\IpWhitelistStatus;
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
            ->label('Revoke')
            ->color('warning')
            ->modalHeading('Revoke IP Whitelist')
            ->modalDescription('Are you sure you want to revoke whitelist access for this IP? The customer will immediately be blocked from calling the API from this IP.')
            ->modalWidth(Width::Medium)
            ->visible(fn (IpWhitelistRequest $record) => $record->status === IpWhitelistStatus::Approved)
            ->schema([
                Textarea::make('admin_note')
                    ->label('Reason for Revocation (Optional)')
                    ->placeholder('e.g. Access revoked due to security audit / policy violation.')
                    ->rows(3),
            ])
            ->action(function (array $data, IpWhitelistRequest $record) {
                $note = ! empty($data['admin_note']) ? $data['admin_note'] : 'Whitelist access was revoked by administrator.';

                $record->update([
                    'status' => IpWhitelistStatus::Rejected,
                    'admin_note' => $note,
                    'reviewed_by' => Filament::auth()->id(),
                    'reviewed_at' => now(),
                ]);

                if ($record->user) {
                    Notification::make()
                        ->title('IP Whitelist Revoked')
                        ->body("Your whitelisted IP {$record->ip_address} has been revoked by admin. Reason: {$note}")
                        ->warning()
                        ->sendToDatabase($record->user);
                }

                Notification::make()
                    ->title('IP Whitelist Revoked')
                    ->body("IP {$record->ip_address} for customer {$record->user?->name} has been revoked.")
                    ->warning()
                    ->send();
            });
    }
}
