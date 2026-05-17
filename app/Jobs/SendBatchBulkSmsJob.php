<?php

namespace App\Jobs;

use App\Contracts\SupportsBulkSms;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\MessageBatch;
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
        public readonly MessageBatch $batch,
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

        $messages = $query->select(['id', 'to'])->orderBy('id')->get();

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
    }

    /**
     * Updates the message rows with the minimum number of queries:
     *
     *   - Same ID for all (gatewayBatchId)  → 1 whereIn UPDATE
     *   - Per-recipient IDs (perRecipientIds) → 1 whereIn UPDATE for common fields
     *                                          + 1 raw CASE WHEN UPDATE for gateway_message_id
     */
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
