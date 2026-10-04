<?php

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Pages;

use App\Enums\IpWhitelistStatus;
use App\Filament\Admin\Pages\AuditLogs\IpWhitelistAuditPage;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\IpWhitelistRequestResource;
use App\Models\IpWhitelistRequest;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListIpWhitelistRequests extends ListRecords
{
    protected static string $resource = IpWhitelistRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_audit_logs')
                ->label('Audit Log')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('gray')
                ->url(fn (): string => IpWhitelistAuditPage::getUrl()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending Review')
                ->badge(fn () => IpWhitelistRequest::where('status', IpWhitelistStatus::Pending)->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->where('status', IpWhitelistStatus::Pending)),

            'approved' => Tab::make('Active / Approved')
                ->badge(fn () => IpWhitelistRequest::where('status', IpWhitelistStatus::Approved)->count() ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn ($query) => $query->where('status', IpWhitelistStatus::Approved)),

            'disabled' => Tab::make('Disabled')
                ->badge(fn () => IpWhitelistRequest::where('status', IpWhitelistStatus::Disabled)->count() ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn ($query) => $query->where('status', IpWhitelistStatus::Disabled)),

            'rejected' => Tab::make('Rejected')
                ->badge(fn () => IpWhitelistRequest::where('status', IpWhitelistStatus::Rejected)->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($query) => $query->where('status', IpWhitelistStatus::Rejected)),

            'all' => Tab::make('All Entries'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }
}
