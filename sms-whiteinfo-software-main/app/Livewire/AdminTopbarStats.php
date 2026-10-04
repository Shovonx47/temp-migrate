<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\DepositStatus;
use App\Enums\IpWhitelistStatus;
use App\Enums\KycStatus;
use App\Models\Deposit;
use App\Models\IpWhitelistRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AdminTopbarStats extends Component
{
    public string $mode = 'desktop';

    public function mount(string $mode = 'desktop'): void
    {
        $this->mode = $mode;
    }

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
                'title'       => 'KYC Pending',
                'short_title' => 'KYC',
                'value'       => (string) $kycPendingCount,
                'url'         => url('/superman/kyc/requests'),
                'theme'       => 'purple',
                'icon'        => 'heroicon-s-identification',
            ],
            'today_deposit' => [
                'title'       => 'Today Deposit',
                'short_title' => 'Deposit',
                'value'       => '৳' . number_format($todayDeposit, 2),
                'url'         => url('/superman/finance/deposits'),
                'theme'       => 'green',
                'icon'        => 'heroicon-s-banknotes',
            ],
            'today_sms' => [
                'title'       => 'Today SMS Sent',
                'short_title' => 'SMS',
                'value'       => number_format($todaySmsCount),
                'url'         => url('/superman/reports/sms'), // Points to SMS Report as requested
                'theme'       => 'blue',
                'icon'        => 'heroicon-s-chat-bubble-left-right',
            ],
            'today_profit' => [
                'title'       => 'Today Profit',
                'short_title' => 'Profit',
                'value'       => '৳' . number_format($todayProfit, 2),
                'url'         => url('/superman/profit/global'),
                'theme'       => 'orange',
                'icon'        => 'heroicon-s-arrow-trending-up',
            ],
            'low_balance' => [
                'title'       => 'Low Balance',
                'short_title' => 'Low Bal',
                'value'       => (string) $lowBalanceCount,
                'url'         => url('/superman/users/customers?tableFilters[balance_tier][value]=low'),
                'theme'       => 'red',
                'icon'        => 'heroicon-s-exclamation-triangle',
            ],
            'open_tickets' => [
                'title'       => 'Open Tickets',
                'short_title' => 'Tickets',
                'value'       => (string) $openTicketsCount,
                'url'         => url('/superman/sms/inquiry'),
                'theme'       => 'cyan',
                'icon'        => 'heroicon-s-ticket',
            ],
            'ip_whitelist' => [
                'title'       => 'IP Whitelist',
                'short_title' => 'IP Whitelist',
                'value'       => (string) $ipWhitelistPendingCount,
                'url'         => url('/superman/approval-center/ip-whitelists'),
                'theme'       => 'indigo',
                'icon'        => 'heroicon-s-shield-check',
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin-topbar-stats', [
            'stats' => $this->getStats(),
            'mode'  => $this->mode,
        ]);
    }
}
