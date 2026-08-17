<?php

namespace App\Console\Commands;

use App\Models\Gateway;
use App\Models\Message;
use App\Models\Operator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('backfill:gateway-costs')]
#[Description('Backfill gateway_cost on existing messages based on gateway operator rates')]
class BackfillGatewayCosts extends Command
{
    public function handle(): int
    {
        $this->info('Loading operator data...');

        $operators = Operator::query()
            ->active()
            ->with(['country' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('id');

        $codeIndex = [];
        foreach ($operators as $operator) {
            if (! $operator->country) {
                continue;
            }
            $countryCode = (string) $operator->country->code;
            foreach ((array) $operator->codes as $code) {
                $codeIndex[$countryCode.':'.(string) $code] = $operator->id;
            }
        }

        $this->info('Loading gateway rates...');

        $gatewayRates = [];
        foreach (Gateway::all() as $gateway) {
            $rates = $gateway->gatewayOperatorRates()->get();
            foreach ($rates as $rate) {
                $gatewayRates[$gateway->id][$rate->operator_id] = [
                    'masking_price' => $rate->masking_price,
                    'non_masking_price' => $rate->non_masking_price,
                ];
            }
        }

        $total = Message::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('gateway_cost', 0)
                    ->orWhere('operator_cost', 0);
            })
            ->whereNotNull('gateway_id')
            ->count();

        if ($total === 0) {
            $this->info('No messages to backfill.');

            return self::SUCCESS;
        }

        $this->info("Found {$total} messages to backfill.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        Message::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('gateway_cost', 0)
                    ->orWhere('operator_cost', 0);
            })
            ->whereNotNull('gateway_id')
            ->select(['id', 'to', 'gateway_id', 'parts'])
            ->chunk(500, function ($messages) use ($operators, $codeIndex, $gatewayRates, &$updated, $bar) {
                $updates = [];

                foreach ($messages as $message) {
                    $phone = (string) $message->to;
                    $digits = ltrim($phone, '+');
                    $operatorId = null;

                    foreach ($codeIndex as $key => $opId) {
                        [$country, $code] = explode(':', $key, 2);
                        if (! str_starts_with($digits, $country)) {
                            continue;
                        }
                        $remainder = substr($digits, strlen($country));
                        if (str_starts_with($remainder, $code)) {
                            $operatorId = $opId;
                            break;
                        }
                    }

                    if ($operatorId === null) {
                        $bar->advance();

                        continue;
                    }

                    $operator = $operators[$operatorId] ?? null;
                    $rates = $gatewayRates[$message->gateway_id][$operatorId] ?? null;

                    $opRate = $operator?->non_masking_price ?? $operator?->masking_price ?? 0;
                    $gwRate = $rates ? ($rates['non_masking_price'] ?? $rates['masking_price'] ?? 0) : 0;

                    $opCost = round((float) $opRate * (int) $message->parts, 4);
                    $gwCost = round((float) $gwRate * (int) $message->parts, 4);

                    $updates[$message->id] = [
                        'operator_cost' => $opCost,
                        'gateway_cost' => $gwCost,
                    ];
                    $updated++;

                    $bar->advance();
                }

                // Batch update
                foreach ($updates as $id => $payload) {
                    DB::table('messages')->where('id', $id)->update($payload);
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Backfilled operator_cost & gateway_cost for {$updated} messages.");

        return self::SUCCESS;
    }
}
