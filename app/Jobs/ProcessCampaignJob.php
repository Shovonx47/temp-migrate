<?php

namespace App\Jobs;

use App\Actions\Billing\DebitBalance;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Models\Campaign;
use App\Models\Message;
use App\Support\OperatorRateResolver;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
    ) {}

    public function handle(): void
    {
        $campaign = $this->campaign->fresh(['user', 'sender']);

        if (! $campaign || $campaign->status !== CampaignStatus::Processing) {
            return;
        }

        $user = $campaign->user;
        $sender = $campaign->sender;

        if (! $sender) {
            $campaign->update([
                'status' => CampaignStatus::Failed,
                'completed_at' => now(),
            ]);

            Notification::make()
                ->title('Campaign sender missing')
                ->body("Campaign '{$campaign->name}' failed because no sender was assigned.")
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->sendToDatabase($user);

            return;
        }

        $pendingRecipients = DB::table('campaign_contact')
            ->where('campaign_id', $campaign->id)
            ->where('status', CampaignRecipientStatus::Pending->value)
            ->get();

        if ($pendingRecipients->isEmpty()) {
            $campaign->update([
                'status' => CampaignStatus::Completed,
                'started_at' => $campaign->started_at ?? now(),
                'completed_at' => now(),
            ]);

            return;
        }

        $resolver = OperatorRateResolver::forUser($user);
        $isMasking = $sender->type?->isMasking() ?? true;

        $recipientPlans = [];
        $totalRequiredCost = 0.0;

        foreach ($pendingRecipients as $recipient) {
            try {
                $rate = $resolver->resolve((string) $recipient->phone, $isMasking);
            } catch (Throwable) {
                $rate = 0.5;
            }

            $recipientPlans[] = [
                'recipient' => $recipient,
                'rate' => $rate,
            ];
            $totalRequiredCost += $rate;
        }

        if ($user->balance < $totalRequiredCost) {
            $campaign->update([
                'status' => CampaignStatus::Failed,
                'completed_at' => now(),
            ]);

            return;
        }

        $campaign->update([
            'started_at' => $campaign->started_at ?? now(),
        ]);

        $debitAction = app(DebitBalance::class);
        $totalSent = (int) $campaign->total_sent;
        $totalFailed = (int) $campaign->total_failed;

        foreach ($recipientPlans as $item) {
            $recipient = $item['recipient'];
            $rate = $item['rate'];

            try {
                DB::transaction(function () use ($campaign, $user, $sender, $recipient, $rate, $debitAction, &$totalSent) {
                    $message = Message::create([
                        'user_id' => $user->id,
                        'sender_id' => $sender->id,
                        'campaign_id' => $campaign->id,
                        'to' => $recipient->phone,
                        'body' => $campaign->body,
                        'parts' => 1,
                        'rate' => $rate,
                        'cost' => $rate,
                        'status' => MessageStatus::Sent,
                        'sent_at' => now(),
                    ]);

                    $debitAction->handle(
                        user: $user,
                        amount: $rate,
                        transactionable: $message,
                        description: "Campaign SMS to {$recipient->phone}"
                    );

                    DB::table('campaign_contact')
                        ->where('id', $recipient->id)
                        ->update([
                            'status' => CampaignRecipientStatus::Sent->value,
                            'message_id' => $message->id,
                            'cost' => $rate,
                            'sent_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $totalSent++;
                });
            } catch (Throwable $e) {
                DB::table('campaign_contact')
                    ->where('id', $recipient->id)
                    ->update([
                        'status' => CampaignRecipientStatus::Failed->value,
                        'error' => $e->getMessage(),
                        'updated_at' => now(),
                    ]);

                $totalFailed++;
            }
        }

        $campaign->update([
            'status' => CampaignStatus::Completed,
            'completed_at' => now(),
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
        ]);
    }
}
