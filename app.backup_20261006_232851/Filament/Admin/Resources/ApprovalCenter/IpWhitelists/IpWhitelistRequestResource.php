<?php

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists;

use App\Enums\IpWhitelistStatus;
use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Pages\ListIpWhitelistRequests;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Tables\IpWhitelistRequestsTable;
use App\Models\IpWhitelistRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IpWhitelistRequestResource extends Resource
{
    protected static ?string $model = IpWhitelistRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::ApprovalCenter;

    protected static ?string $navigationLabel = 'IP Whitelist Requests';

    protected static ?string $modelLabel = 'IP Whitelist Request';

    protected static ?string $pluralModelLabel = 'IP Whitelist Requests';

    protected static ?string $slug = 'approval-center/ip-whitelists';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = IpWhitelistRequest::where('status', IpWhitelistStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return IpWhitelistRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIpWhitelistRequests::route('/'),
        ];
    }
}
