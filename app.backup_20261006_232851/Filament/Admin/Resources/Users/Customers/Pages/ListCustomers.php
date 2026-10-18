<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Filament\Exports\UserExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected string $view = 'filament.admin.resources.customers.list-customers';

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(UserExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTotalCalculationData(): array
    {
        $query = $this->getFilteredTableQuery();

        $totalCustomers = (clone $query)->count();
        $totalBalance = (float) ((clone $query)->sum('balance') ?? 0);
        $avgBalance = $totalCustomers > 0 ? (float) ((clone $query)->avg('balance') ?? 0) : 0;

        $fundedCount = (clone $query)->where('balance', '>', 0)->count();
        $zeroBalanceCount = (clone $query)->where('balance', '<=', 0)->count();
        $withNidCount = (clone $query)->whereNotNull('nid_number')->where('nid_number', '!=', '')->count();

        $verifiedKycCount = (clone $query)->where('kyc_status', KycStatus::Verified->value)->count();
        $underReviewKycCount = (clone $query)->where('kyc_status', KycStatus::UnderReview->value)->count();
        $otherKycCount = max(0, $totalCustomers - ($verifiedKycCount + $underReviewKycCount));

        $approvedStatusCount = (clone $query)->where('status', UserStatus::Approved->value)->count();
        $pendingStatusCount = (clone $query)->where('status', UserStatus::Pending->value)->count();
        $inactiveStatusCount = (clone $query)->whereIn('status', [UserStatus::Rejected->value, UserStatus::Banned->value])->count();

        $kycVerifiedRate = $totalCustomers > 0 ? round(($verifiedKycCount / $totalCustomers) * 100, 1) : 0;
        $approvedRate = $totalCustomers > 0 ? round(($approvedStatusCount / $totalCustomers) * 100, 1) : 0;
        $fundedPercentage = $totalCustomers > 0 ? round(($fundedCount / $totalCustomers) * 100, 1) : 0;

        return [
            'total_customers' => $totalCustomers,
            'total_balance' => $totalBalance,
            'avg_balance' => $avgBalance,
            'funded_customers_count' => $fundedCount,
            'zero_balance_count' => $zeroBalanceCount,
            'with_nid_count' => $withNidCount,
            'verified_kyc_count' => $verifiedKycCount,
            'under_review_kyc_count' => $underReviewKycCount,
            'other_kyc_count' => $otherKycCount,
            'kyc_verified_rate' => $kycVerifiedRate,
            'approved_status_count' => $approvedStatusCount,
            'pending_status_count' => $pendingStatusCount,
            'inactive_status_count' => $inactiveStatusCount,
            'approved_rate' => $approvedRate,
            'funded_percentage' => $fundedPercentage,
        ];
    }
}
