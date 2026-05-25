<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Contracts\SupportsBulkSms;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\MessageStatus;
use App\Models\Batch;
use App\Models\Message;
use App\Models\User;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Support\Facades\Sms;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sends a chunk of pending messages as a single bulk API request.
 *
 * One job dispatch = one gateway API call. Rate limiting is enforced via the
 * 'sms' RateLimiter keyed by gateway ID (requests_per_minute on the Gateway model).
 *
 * Use this job when:
 *   - The batch body has no {variable} placeholders, AND
 *   - The resolved driver implements SupportsBulkSms
 */
class SendBatchBulkSmsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    /**
     * @param  int[]  $messageIds  The specific pending message IDs to send in this bulk call.
     */
    public function __construct(
        public readonly Batch $batch,
        public readonly array $messageIds = [],
    ) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new RateLimited('sms')];
    }

    public function retryUntil(): CarbonInterface
    {
        return now()->addHour();
    }

    public function handle(): void
    {
        $batch = $this->batch->fresh(['sender.gateway']);

        if (! $batch) {
            return;
        }

        $gateway = $batch->sender?->gateway;

        if (! $gateway) {
            return;
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsBulkSms) {
            return;
        }

        $from = $batch->sender?->sender_id;

        $query = $this->messageIds !== []
            ? Message::whereIn('id', $this->messageIds)->where('status', MessageStatus::Pending)
            : $batch->messages()->where('status', MessageStatus::Pending);

        $messages = $query->select(['id', 'to', 'cost'])->orderBy('id')->get();

        if ($messages->isEmpty()) {
            return;
        }

        $recipients = $messages->map(fn (Message $m): string => (string) $m->to)->all();

        $response = $driver->sendBulk(new BulkSmsRequest(
            recipients: $recipients,
            body: $batch->body,
            from: $from,
        ));

        $this->applyBulkResponse($messages, $response);

        if (! $response->successful) {
            $this->refundFailedMessages($batch, $messages);
        }

        // Schedule DLR polling via DB so the command can pick it up even after a Redis flush.
        if ($response->successful && $driver instanceof SupportsDeliveryStatus) {
            $this->schedulePollJobs($messages->pluck('id')->all());
        }
    }

    /**
     * Refunds the total cost of failed messages back to the user's balance.
     * Uses the batch as the transactionable so the credit appears alongside the original debit.
     */
    private function refundFailedMessages(Batch $batch, Collection $messages): void
    {
        $totalCost = round((float) $messages->sum('cost'), 4);

        if ($totalCost <= 0) {
            return;
        }

        DB::transaction(function () use ($batch, $messages, $totalCost): void {
            app(CreditBalance::class)->handle(
                user: User::findOrFail($batch->user_id),
                amount: $totalCost,
                transactionable: $batch,
                description: 'Refund: bulk SMS send failed ('.$messages->count().' messages)',
            );
        });
    }

    /**
     * Updates the message rows with the minimum number of queries:
     *
     *   - Same ID for all (gatewayBatchId)  → 1 whereIn UPDATE
     *   - Per-recipient IDs (perRecipientIds) → 1 whereIn UPDATE for common fields
     *                                          + 1 raw CASE WHEN UPDATE for gateway_message_id
     */
    /**
     * Writes `poll_attempt = 1` and `next_poll_at = now + 2 min` for every message
     * that received a gateway_message_id. The `messages:poll-delivery` command will
     * pick these up and dispatch PollMessageDeliveryJob, making the schedule resilient
     * to Redis flushes.
     *
     * @param  int[]  $ids  The message IDs that were just updated.
     */
    private function schedulePollJobs(array $ids): void
    {
        Message::whereIn('id', $ids)
            ->whereNotNull('gateway_message_id')
            ->update([
                'poll_attempt' => 1,
                'next_poll_at' => now()->addMinutes(2),
            ]);
    }

    private function applyBulkResponse(Collection $messages, BulkSmsResponse $response): void
    {
        $ids = $messages->pluck('id')->all();
        $now = Carbon::now();

        $commonUpdate = [
            'status' => $response->successful ? MessageStatus::Sent->value : MessageStatus::Failed->value,
            'sent_at' => $response->successful ? $now : null,
            'failed_at' => $response->successful ? null : $now,
            'failed_reason' => $response->errorMessage,
            'updated_at' => $now,
        ];

        if ($response->perRecipientIds === []) {
            // Same ID (or null) for every recipient — single query.
            Message::whereIn('id', $ids)->update(
                array_merge($commonUpdate, ['gateway_message_id' => $response->gatewayBatchId]),
            );

            return;
        }

        // Per-recipient IDs: update common fields first, then gateway_message_id via CASE WHEN.
        Message::whereIn('id', $ids)->update($commonUpdate);

        $caseWhen = 'CASE';
        $caseBindings = [];

        foreach ($messages as $index => $message) {
            $caseWhen .= ' WHEN id = ? THEN ?';
            $caseBindings[] = $message->id;
            $caseBindings[] = $response->perRecipientIds[$index] ?? null;
        }

        $caseWhen .= ' END';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        DB::update(
            "UPDATE messages SET gateway_message_id = {$caseWhen} WHERE id IN ({$placeholders})",
            array_merge($caseBindings, $ids),
        );
    }
}
