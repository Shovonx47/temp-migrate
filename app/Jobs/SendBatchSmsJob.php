<?php

namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Contracts\SupportsBulkSms;
use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Data\SmsRequest;
use App\Support\Facades\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Delivers pending Message rows for a completed MessageBatch via the configured
 * SMS gateway. Chooses between two strategies per batch:
 *
 *   - Bulk   — no {variable} placeholders in body AND driver implements SupportsBulkSms:
 *              All recipients share the same body. One API call per chunk of 1 000.
 *              Status rows are updated in 1–2 queries per chunk (whereIn / CASE WHEN).
 *
 *   - Individual — variable body OR driver does not support bulk:
 *              One API call per message. Status rows are still updated with a single
 *              whereIn query per chunk to minimise round-trips.
 */
class SendBatchSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum recipients per bulk API call and messages per individual chunk.
     * ReveSMS documentation recommends ≤ 1 000 numbers per request.
     */
    private const int CHUNK_SIZE = 1000;

    public int $timeout = 0;

    public int $tries = 1;

    public function __construct(public readonly MessageBatch $batch) {}

    public function handle(): void
    {
        // Eager-load sender → gateway in one query.
        $batch = $this->batch->fresh(['sender.gateway']);

        if (! $batch) {
            return;
        }

        $gateway = $batch->sender?->gateway;

        if (! $gateway) {
            return;
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $batch->hasBodyVariables() && $driver instanceof SupportsBulkSms) {
            $this->sendBulk($batch, $driver);
        } else {
            $this->sendIndividual($batch, $driver);
        }
    }

    // -------------------------------------------------------------------------
    // Bulk strategy
    // -------------------------------------------------------------------------

    private function sendBulk(MessageBatch $batch, SupportsBulkSms $driver): void
    {
        $from = $batch->sender?->sender_id;

        $batch->messages()
            ->where('status', MessageStatus::Pending)
            ->select(['id', 'to'])
            ->chunkById(self::CHUNK_SIZE, function (Collection $messages) use ($driver, $batch, $from): void {
                $recipients = $messages->map(fn (Message $m): string => (string) $m->to)->all();

                $response = $driver->sendBulk(new BulkSmsRequest(
                    recipients: $recipients,
                    body: $batch->body,
                    from: $from,
                ));

                $this->applyBulkResponse($messages, $response);
            });
    }

    /**
     * Applies a bulk response to the chunk with the minimum number of queries:
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
            // Same ID for every recipient, or null — single query.
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

    // -------------------------------------------------------------------------
    // Individual strategy
    // -------------------------------------------------------------------------

    private function sendIndividual(MessageBatch $batch, SmsGateway $driver): void
    {
        $from = $batch->sender?->sender_id;

        $batch->messages()
            ->where('status', MessageStatus::Pending)
            ->select(['id', 'to', 'body'])
            ->chunkById(self::CHUNK_SIZE, function (Collection $messages) use ($driver, $from): void {
                $this->sendIndividualChunk($messages, $driver, $from);
            });
    }

    /**
     * Sends each message individually and collects results, then flushes all
     * status updates in two bulk queries (sent + failed groups).
     *
     * @param  Collection<int, Message>  $messages
     */
    private function sendIndividualChunk(Collection $messages, SmsGateway $driver, ?string $from): void
    {
        $now = Carbon::now();
        $sent = [];   // [id => gateway_message_id]
        $failed = []; // [id => error_message]

        foreach ($messages as $message) {
            try {
                $response = $driver->send(new SmsRequest(
                    to: (string) $message->to,
                    body: $message->body,
                    from: $from,
                ));

                if ($response->successful) {
                    $sent[$message->id] = $response->gatewayMessageId;
                } else {
                    $failed[$message->id] = $response->errorMessage;
                }
            } catch (Throwable $e) {
                $failed[$message->id] = $e->getMessage();
            }
        }

        if ($sent !== []) {
            $this->bulkMarkSent($sent, $now);
        }

        if ($failed !== []) {
            $this->bulkMarkFailed($failed, $now);
        }
    }

    /**
     * Updates sent messages: common fields via whereIn, gateway_message_ids via CASE WHEN.
     *
     * @param  array<int, string|null>  $sentMap  id → gateway_message_id
     */
    private function bulkMarkSent(array $sentMap, Carbon $now): void
    {
        $ids = array_keys($sentMap);

        Message::whereIn('id', $ids)->update([
            'status' => MessageStatus::Sent->value,
            'sent_at' => $now,
            'failed_at' => null,
            'failed_reason' => null,
            'updated_at' => $now,
        ]);

        // Only issue the CASE WHEN query when we actually have IDs to store.
        $idsWithValue = array_filter($sentMap, fn (?string $id): bool => $id !== null && $id !== '');

        if ($idsWithValue === []) {
            return;
        }

        $caseWhen = 'CASE';
        $caseBindings = [];

        foreach ($idsWithValue as $msgId => $gatewayId) {
            $caseWhen .= ' WHEN id = ? THEN ?';
            $caseBindings[] = $msgId;
            $caseBindings[] = $gatewayId;
        }

        $caseWhen .= ' END';
        $filteredIds = array_keys($idsWithValue);
        $placeholders = implode(',', array_fill(0, count($filteredIds), '?'));

        DB::update(
            "UPDATE messages SET gateway_message_id = {$caseWhen} WHERE id IN ({$placeholders})",
            array_merge($caseBindings, $filteredIds),
        );
    }

    /**
     * Updates failed messages with their error reasons.
     *
     * @param  array<int, string|null>  $failedMap  id → error_message
     */
    private function bulkMarkFailed(array $failedMap, Carbon $now): void
    {
        $ids = array_keys($failedMap);

        Message::whereIn('id', $ids)->update([
            'status' => MessageStatus::Failed->value,
            'sent_at' => null,
            'failed_at' => $now,
            'updated_at' => $now,
        ]);

        // Store individual error reasons via CASE WHEN.
        $caseWhen = 'CASE';
        $caseBindings = [];

        foreach ($failedMap as $msgId => $reason) {
            $caseWhen .= ' WHEN id = ? THEN ?';
            $caseBindings[] = $msgId;
            $caseBindings[] = $reason;
        }

        $caseWhen .= ' END';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        DB::update(
            "UPDATE messages SET failed_reason = {$caseWhen} WHERE id IN ({$placeholders})",
            array_merge($caseBindings, $ids),
        );
    }
}
