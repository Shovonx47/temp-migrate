<?php

namespace App\Services\Billing;

use App\Enums\MessageStatus;
use App\Models\Gateway;
use App\Models\User;
use App\Services\Billing\DTO\RevenueResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueResolutionService
{
    /**
     * Revenue is always derived from tariff snapshotted on messages — never from gateway data.
     */
    public function resolveForPeriod(Carbon $from, Carbon $to, ?Gateway $gateway = null): RevenueResult
    {
        $query = DB::table('messages')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', MessageStatus::Failed->value);

        if ($gateway !== null) {
            $query->where('gateway_id', $gateway->id);
        }

        $result = $query->selectRaw('
            COUNT(*) as message_count,
            COALESCE(SUM(cost), 0) as revenue,
            COALESCE(SUM(parts), 0) as parts
        ')->first();

        return new RevenueResult(
            total: round((float) ($result->revenue ?? 0), 4),
            messageCount: (int) ($result->message_count ?? 0),
            parts: (int) ($result->parts ?? 0),
        );
    }

    public function resolveForCustomer(User $user, Carbon $from, Carbon $to): RevenueResult
    {
        $result = DB::table('messages')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->where('status', '!=', MessageStatus::Failed->value)
            ->selectRaw('
                COUNT(*) as message_count,
                COALESCE(SUM(cost), 0) as revenue,
                COALESCE(SUM(parts), 0) as parts
            ')
            ->first();

        return new RevenueResult(
            total: round((float) ($result->revenue ?? 0), 4),
            messageCount: (int) ($result->message_count ?? 0),
            parts: (int) ($result->parts ?? 0),
        );
    }
}
