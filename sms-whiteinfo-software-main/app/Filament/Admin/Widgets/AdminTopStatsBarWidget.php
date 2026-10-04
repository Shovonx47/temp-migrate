<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\DepositStatus;
use App\Enums\IpWhitelistStatus;
use App\Enums\KycStatus;
use App\Models\Deposit;
use App\Models\IpWhitelistRequest;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminTopStatsBarWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.admin-top-stats-bar';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -100;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getStats(): array
    {
        $today = today();

        // 1. KYC Pending (Purple)
        $kycPendingCount = User::where('type', 'customer')
            ->where('kyc_status', KycStatus::UnderReview)
            ->count();

        // 2. Today Deposit (Green)
        $todayDeposit = (float) (Deposit::whereDate('created_at', $today)
            ->where('status', DepositStatus::Completed)
            ->sum('amount') ?? 0);

        // 3. Today SMS Sent (Blue)
        $todaySmsCount = Message::whereDate('created_at', $today)
            ->whereIn('status', ['sent', 'delivered'])
            ->count();

        // 4. Today Profit (Orange)
        $todayProfit = (float) (Message::whereDate('created_at', $today)
            ->selectRaw('SUM(cost - (operator_cost + primary_dipping_cost)) as profit')
            ->value('profit') ?? 0);

        // 5. Low Balance Customers (< 100 BDT) (Red)
        $lowBalanceCount = User::where('type', 'customer')
            ->where('balance', '<', 100)
            ->count();

        // 6. Open Tickets (Cyan)
        $openTicketsCount = Schema::hasTable('tickets')
            ? (int) DB::table('tickets')->whereIn('status', ['open', 'pending'])->count()
            : 0;

        // 7. IP Whitelist Pending (Indigo)
        $ipWhitelistPendingCount = IpWhitelistRequest::where('status', IpWhitelistStatus::Pending)
            ->count();

        return [
            'kyc_pending' => [
                'title' => 'KYC Pending',
                'value' => number_format($kycPendingCount),
                'url' => url('/superman/kyc/requests'),
                'theme' => 'purple',
                'icon' => 'heroicon-o-identification',
            ],
            'today_deposit' => [
                'title' => 'Today Deposit',
                'value' => 'BDT ' . number_format($todayDeposit, 2),
                'url' => url('/superman/finance/deposits'),
                'theme' => 'green',
                'icon' => 'heroicon-o-banknotes',
            ],
            'today_sms' => [
                'title' => 'Today SMS Sent',
                'value' => number_format($todaySmsCount),
                'url' => url('/superman/sms/logs'),
                'theme' => 'blue',
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
            'today_profit' => [
                'title' => 'Today Profit',
                'value' => 'BDT ' . number_format($todayProfit, 2),
                'url' => url('/superman/profit/global'),
                'theme' => 'orange',
                'icon' => 'heroicon-o-arrow-trending-up',
            ],
            'low_balance' => [
                'title' => 'Low Balance',
                'value' => number_format($lowBalanceCount),
                'url' => url('/superman/users/customers?tableFilters[balance_tier][value]=low'),
                'theme' => 'red',
                'icon' => 'heroicon-o-exclamation-triangle',
            ],
            'open_tickets' => [
                'title' => 'Open Tickets',
                'value' => number_format($openTicketsCount),
                'url' => url('/superman/sms/inquiry'),
                'theme' => 'cyan',
                'icon' => 'heroicon-o-ticket',
            ],
            'ip_whitelist' => [
                'title' => 'IP Whitelist',
                'value' => number_format($ipWhitelistPendingCount),
                'url' => url('/superman/approval-center/ip-whitelists'),
                'theme' => 'indigo',
                'icon' => 'heroicon-o-shield-check',
            ],
        ];
    }
}
