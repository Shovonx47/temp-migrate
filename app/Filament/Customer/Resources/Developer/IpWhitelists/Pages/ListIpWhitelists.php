<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists\Pages;

use App\Enums\IpWhitelistStatus;
use App\Filament\Customer\Resources\Developer\IpWhitelists\IpWhitelistResource;
use App\Models\IpWhitelistAuditLog;
use App\Models\IpWhitelistRequest;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListIpWhitelists extends ListRecords
{
    protected static string $resource = IpWhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Request IP Whitelist')
                ->modalHeading('Request IP Whitelist')
                ->modalDescription('Submit an IP address or CIDR range to be whitelisted for API access. Requests require admin approval before becoming active.')
                ->modalWidth(Width::Medium)
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = Filament::auth()->id();
                    $data['status']  = IpWhitelistStatus::Pending;

                    return $data;
                })
                ->after(function (IpWhitelistRequest $record): void {
                    IpWhitelistAuditLog::record(
                        userId: $record->user_id,
                        action: 'created',
                        ipAddress: $record->ip_address,
                        requestId: $record->id,
                        oldValues: null,
                        newValues: [
                            'ip_address' => $record->ip_address,
                            'label'      => $record->label,
                            'status'     => $record->status->value,
                        ],
                        note: 'Requested by customer',
                    );
                })
                ->successNotification(
                    Notification::make()
                        ->title('Request Submitted')
                        ->body('Your IP whitelist request has been submitted for admin approval.')
                        ->success()
                ),
        ];
    }
}
