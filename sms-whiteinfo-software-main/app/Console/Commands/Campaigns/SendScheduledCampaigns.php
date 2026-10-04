<?php

namespace App\Console\Commands\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Support\CampaignDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('campaigns:send-scheduled')]
#[Description('Dispatch any scheduled campaigns whose scheduled_at has fallen due.')]
class SendScheduledCampaigns extends Command
{
    public function handle(CampaignDispatcher $dispatcher): int
    {
        $now = now();

        $dispatched = 0;

        Campaign::query()
            ->where('status', CampaignStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->chunkById(50, function ($campaigns) use ($dispatcher, &$dispatched): void {
                foreach ($campaigns as $campaign) {
                    $dispatcher->dispatchNow($campaign);
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} scheduled campaign(s).");

        return self::SUCCESS;
    }
}
