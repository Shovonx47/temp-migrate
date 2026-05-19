<?php

namespace App\Filament\Admin\Resources\Messaging\Approvals;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Resources\Messaging\Approvals\Pages\ListApprovals;
use App\Filament\Admin\Resources\Messaging\Approvals\Tables\ApprovalsTable;
use App\Models\Batch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApprovalResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Messaging;

    protected static ?string $slug = 'messaging/approvals';

    protected static ?string $modelLabel = 'Approvals';

    public static function getNavigationBadge(): ?string
    {
        return Batch::whereApproval('pending')->count();
    }

    public static function table(Table $table): Table
    {
        return ApprovalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovals::route('/'),
        ];
    }
}
