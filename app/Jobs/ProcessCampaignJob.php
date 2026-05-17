<?php

namespace App\Jobs;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OperatorRateNotConfiguredException;
use App\Exceptions\SenderRequiredException;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Processes a campaign by iterating its pre-snapshotted campaign_contact rows
 * in chunks, pricing each row via the per-message operator rate, debiting the
 * user's balance, inserting a Message row, and updating the pivot with the
 * final status.
 *
 * Mirrors ProcessMessageBatchJob's atomicity contract: a single chunk's
 * priceInsertAndDebit() is wrapped in a transaction so an InsufficientBalance
 * mid-chunk rolls back that chunk entirely (no half-sent state).
 */
class ProcessCampaignJob implements ShouldQueue
{
    use Queueable;

    private const int CHUNK_SIZE = 500;

    public int $timeout = 0;

    public int $tries = 1;

    private ?OperatorRateResolver $rateResolver = null;

    private bool $isMaskingSender = false;

    public function __construct(public readonly Campaign $campaign) {}

    public function handle(): void
    {
        $campaign = $this->campaign->fresh();

        if (! $campaign) {
            return;
        }

        // Concurrent retry guard — only proceed if the campaign is Processing.
        if ($campaign->status !== CampaignStatus::Processing) {
            return;
        }

        if ($campaign->started_at === null) {
            $campaign->update(['started_at' => now()]);
        }

        try {
            $this->initialiseBilling($campaign);

            $hasMore = true;

            while ($hasMore) {
                $rows = DB::table('campaign_contact')
                    ->where('campaign_id', $campaign->id)
                    ->where('status', CampaignRecipientStatus::Pending->value)
                    ->orderBy('id')
                    ->limit(self::CHUNK_SIZE)
                    ->get();

                if ($rows->isEmpty()) {
                    $hasMore = false;

                    break;
                }

                $this->processChunk($campaign, $rows->all());

                // Refresh the campaign to honour mid-run cancellation.
                $campaign->refresh();
                if ($campaign->status !== CampaignStatus::Processing) {
                    return;
                }
            }

            $this->finalize($campaign->fresh());
        } catch (InsufficientBalanceException $e) {
            $this->markFailed($campaign->fresh());
            $this->notifyInsufficientBalance($campaign, $e);
        } catch (SenderRequiredException) {
            $this->markFailed($campaign->fresh());
            $this->notifySenderRequired($campaign);
        } catch (OperatorRateNotConfiguredException $e) {
            $this->markFailed($campaign->fresh());
            $this->notifyOperatorRateMissing($campaign, $e);
        } catch (Throwable $e) {
            $this->markFailed($campaign->fresh());
            $this->notifyFailure($campaign, $e);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $campaign = $this->campaign->fresh();

        if ($campaign && $campaign->status === CampaignStatus::Processing) {
            $this->markFailed($campaign);
            $this->notifyFailure($campaign, $e);
        }
    }

    /**
     * @param  array<int, object>  $rows  campaign_contact rows
     */
    private function processChunk(Campaign $campaign, array $rows): void
    {
        DB::transaction(function () use ($campaign, $rows): void {
            $resolver = $this->rateResolver;

            if ($resolver === null) {
                throw new SenderRequiredException;
            }

            $user = User::query()
                ->whereKey($campaign->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();
            $priced = [];
            $totalCost = 0.0;

            foreach ($rows as $row) {
                $phone = (string) $row->phone;
                $body = $campaign->body;
                $metrics = MessageMetrics::for($body);
                $rate = $resolver->resolve($phone, $this->isMaskingSender);
                $cost = round($rate * $metrics->parts, 4);

                $priced[] = [
                    'recipient_id' => (int) $row->id,
                    'contact_id' => (int) $row->contact_id,
                    'phone' => $phone,
                    'body' => $body,
                    'parts' => $metrics->parts,
                    'rate' => $rate,
                    'cost' => $cost,
                ];

                $totalCost = round($totalCost + $cost, 4);
            }

            $balanceBefore = (float) $user->balance;

            if ($balanceBefore < $totalCost) {
                throw new InsufficientBalanceException(
                    required: $totalCost,
                    available: $balanceBefore,
                );
            }

            $balanceAfter = round($balanceBefore - $totalCost, 4);
            $user->forceFill(['balance' => $balanceAfter])->save();

            $maxIdBefore = (int) DB::table('messages')
                ->where('campaign_id', $campaign->id)
                ->max('id');

            $messageRows = [];

            foreach ($priced as $p) {
                $messageRows[] = [
                    'user_id' => $campaign->user_id,
                    'campaign_id' => $campaign->id,
                    'message_batch_id' => null,
                    'sender_id' => $campaign->sender_id,
                    'to' => $p['phone'],
                    'body' => $p['body'],
                    'status' => 'pending',
                    'parts' => $p['parts'],
                    'rate' => $p['rate'],
                    'cost' => $p['cost'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Message::insert($messageRows);

            $inserted = DB::table('messages')
                ->where('campaign_id', $campaign->id)
                ->where('id', '>', $maxIdBefore)
                ->orderBy('id')
                ->get(['id', 'to', 'cost'])
                ->all();

            // Map inserted message rows back to their pivot rows by phone.
            // Phones can repeat across contacts in theory, but campaign_contact
            // is unique per (campaign, contact), so we walk them in insert order.
            $running = $balanceBefore;
            $txnRows = [];

            foreach ($inserted as $i => $msg) {
                $pricedRow = $priced[$i];
                $cost = (float) $msg->cost;
                $next = round($running - $cost, 4);

                DB::table('campaign_contact')
                    ->where('id', $pricedRow['recipient_id'])
                    ->update([
                        'message_id' => (int) $msg->id,
                        'status' => CampaignRecipientStatus::Sent->value,
                        'cost' => $cost,
                        'sent_at' => $now,
                        'updated_at' => $now,
                    ]);

                $txnRows[] = [
                    'user_id' => $campaign->user_id,
                    'transactionable_type' => 'message',
                    'transactionable_id' => (int) $msg->id,
                    'amount' => $cost,
                    'direction' => 'debit',
                    'balance_before' => $running,
                    'balance_after' => $next,
                    'description' => 'Campaign SMS cost',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $running = $next;
            }

            if ($txnRows !== []) {
                DB::table('transactions')->insert($txnRows);
            }

            // Advance counters + cursor.
            $sentDelta = count($priced);
            DB::table('campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'total_sent' => DB::raw("total_sent + {$sentDelta}"),
                    'last_processed_recipient_id' => $priced[array_key_last($priced)]['recipient_id'],
                    'updated_at' => $now,
                ]);
        });
    }

    private function finalize(?Campaign $campaign): void
    {
        if (! $campaign) {
            return;
        }

        $campaign->update([
            'status' => CampaignStatus::Completed,
            'completed_at' => now(),
        ]);

        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        $sent = (int) $campaign->total_sent;
        $failed = (int) $campaign->total_failed;

        $title = "Campaign sent: {$sent} message".($sent === 1 ? '' : 's');
        $body = $failed > 0
            ? "{$failed} recipient".($failed === 1 ? '' : 's').' could not be sent.'
            : 'All recipients were processed successfully.';

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->icon(Heroicon::Megaphone);

        $failed > 0 ? $notification->warning() : $notification->success();

        $notification->sendToDatabase($user);
    }

    private function markFailed(?Campaign $campaign): void
    {
        if (! $campaign) {
            return;
        }

        $campaign->update([
            'status' => CampaignStatus::Failed,
            'completed_at' => now(),
        ]);
    }

    private function initialiseBilling(Campaign $campaign): void
    {
        if ($campaign->sender_id === null) {
            throw new SenderRequiredException;
        }

        $sender = Sender::find($campaign->sender_id);
        if (! $sender || $sender->type === null) {
            throw new SenderRequiredException;
        }

        $user = User::findOrFail($campaign->user_id);

        $this->isMaskingSender = $sender->type->isMasking();
        $this->rateResolver = OperatorRateResolver::forUser($user);
    }

    private function notifyFailure(Campaign $campaign, Throwable $e): void
    {
        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Campaign failed')
            ->body($e->getMessage())
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }

    private function notifyInsufficientBalance(Campaign $campaign, InsufficientBalanceException $e): void
    {
        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        $shortfall = number_format(max($e->required - $e->available, 0), 2);

        Notification::make()
            ->title('Campaign paused: insufficient balance')
            ->body("You need ৳{$shortfall} more to continue. Top up your balance and retry the campaign.")
            ->icon(Heroicon::Banknotes)
            ->danger()
            ->sendToDatabase($user);
    }

    private function notifySenderRequired(Campaign $campaign): void
    {
        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Campaign failed: sender missing')
            ->body('This campaign has no sender selected. Pricing depends on the sender type, so we cannot process it.')
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }

    private function notifyOperatorRateMissing(Campaign $campaign, OperatorRateNotConfiguredException $e): void
    {
        $user = User::find($campaign->user_id);
        if (! $user) {
            return;
        }

        $type = $e->isMasking ? 'masking' : 'non-masking';
        $operator = $e->operatorName ?? 'unknown operator';

        Notification::make()
            ->title('Campaign failed: pricing not configured')
            ->body("No {$type} rate is configured for {$operator}. Please contact support.")
            ->icon(Heroicon::ExclamationTriangle)
            ->danger()
            ->sendToDatabase($user);
    }
}
