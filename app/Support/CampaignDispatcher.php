<?php

namespace App\Support;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Handles campaign lifecycle transitions: snapshotting recipients into the
 * campaign_contact pivot at creation time and dispatching the worker job.
 *
 * The snapshot is taken when the campaign is created — new contacts added to
 * the group after that point are NOT included in the campaign.
 */
class CampaignDispatcher
{
    private const string DEFAULT_COUNTRY = 'BD';

    /**
     * Snapshot the group's contacts into campaign_contact rows. Returns the
     * number of recipients written. Idempotent: skips rows that already exist
     * for this campaign.
     */
    public function snapshotRecipients(Campaign $campaign): int
    {
        if ($campaign->group_id === null) {
            return 0;
        }

        $now = now();
        $count = 0;

        Contact::query()
            ->whereHas('groups', fn ($q) => $q->where('groups.id', $campaign->group_id))
            ->orderBy('id')
            ->chunkById(500, function ($contacts) use ($campaign, $now, &$count): void {
                $rows = [];

                foreach ($contacts as $contact) {
                    $phone = $this->normalizePhone((string) $contact->phone);

                    if ($phone === null) {
                        continue;
                    }

                    $rows[] = [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'message_id' => null,
                        'phone' => $phone,
                        'status' => CampaignRecipientStatus::Pending->value,
                        'error' => null,
                        'cost' => null,
                        'sent_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('campaign_contact')->insertOrIgnore($rows);
                    $count += count($rows);
                }
            });

        $campaign->update(['total_recipients' => $count]);

        return $count;
    }

    /**
     * Dispatch a campaign for immediate processing (after DB commit).
     */
    public function dispatchNow(Campaign $campaign): void
    {
        $campaign->update([
            'status' => CampaignStatus::Processing,
            'scheduled_at' => null,
        ]);

        ProcessCampaignJob::dispatch($campaign)
            ->onQueue('campaigns')
            ->afterCommit();
    }

    /**
     * Mark the campaign as scheduled. The SendScheduledCampaignsCommand will
     * pick it up when its scheduled_at falls due.
     */
    public function schedule(Campaign $campaign, \DateTimeInterface $at): void
    {
        $campaign->update([
            'status' => CampaignStatus::Scheduled,
            'scheduled_at' => $at,
        ]);
    }

    /**
     * Cancel a campaign — only allowed before it has reached a terminal state.
     * Pending recipient rows are marked Skipped so they don't get sent on retry.
     */
    public function cancel(Campaign $campaign): bool
    {
        $campaign->refresh();

        if ($campaign->status->isFinal()) {
            return false;
        }

        DB::transaction(function () use ($campaign): void {
            DB::table('campaign_contact')
                ->where('campaign_id', $campaign->id)
                ->where('status', CampaignRecipientStatus::Pending->value)
                ->update([
                    'status' => CampaignRecipientStatus::Skipped->value,
                    'error' => 'Campaign cancelled',
                    'updated_at' => now(),
                ]);

            $campaign->update([
                'status' => CampaignStatus::Cancelled,
                'completed_at' => now(),
            ]);
        });

        return true;
    }

    private function normalizePhone(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        try {
            $phone = new PhoneNumber($raw, self::DEFAULT_COUNTRY);

            if (! $phone->isValid()) {
                return null;
            }

            return $phone->formatE164();
        } catch (Throwable) {
            return null;
        }
    }
}
