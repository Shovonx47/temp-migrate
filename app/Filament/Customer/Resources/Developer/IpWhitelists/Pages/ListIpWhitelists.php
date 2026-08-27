<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists\Pages;

use App\Enums\IpWhitelistStatus;
use App\Filament\Customer\Resources\Developer\IpWhitelists\IpWhitelistResource;
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
                    $data['status'] = IpWhitelistStatus::Pending;

                    return $data;
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
