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

class RejectIpAction
{
    public static function make(?string $name = 'reject_ip'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedXCircle)
            ->label('Reject')
            ->color('danger')
            ->modalHeading('Reject IP Whitelist Request')
            ->modalDescription('Provide a reason for rejecting this IP whitelist request so the customer knows why.')
            ->modalWidth(Width::Medium)
            ->visible(fn (IpWhitelistRequest $record) => $record->status === IpWhitelistStatus::Pending)
            ->schema([
                Textarea::make('admin_note')
                    ->label('Rejection Reason / Remark')
                    ->placeholder('e.g. Invalid IP format or verification failed. Please provide your official static IP.')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data, IpWhitelistRequest $record) {
                $record->update([
                    'status' => IpWhitelistStatus::Rejected,
                    'admin_note' => $data['admin_note'],
                    'reviewed_by' => Filament::auth()->id(),
                    'reviewed_at' => now(),
                ]);

                if ($record->user) {
                    Notification::make()
                        ->title('IP Whitelist Rejected')
                        ->body("Your IP whitelist request for {$record->ip_address} was rejected. Reason: {$data['admin_note']}")
                        ->danger()
                        ->sendToDatabase($record->user);
                }

                Notification::make()
                    ->title('IP Whitelist Rejected')
                    ->body("IP request {$record->ip_address} for customer {$record->user?->name} has been rejected.")
                    ->warning()
                    ->send();
            });
    }
}
