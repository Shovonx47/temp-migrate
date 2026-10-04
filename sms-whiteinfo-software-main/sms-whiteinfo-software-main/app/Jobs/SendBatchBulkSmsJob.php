<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Contracts\SupportsBulkSms;
use App\Contracts\SupportsDeliveryStatus;
use App\Enums\MessageStatus;
use App\Models\Batch;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\User;
use App\Services\Logging\SmsLogger;
use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;
use App\Services\Sms\Routing\SmsRouteResolver;
use App\Settings\SmsSettings;
use App\Support\Facades\Sms;
use App\Support\InfozillionMessageSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sends a chunk of pending messages as a single bulk API request.
 *
 * One job dispatch = one gateway API call. Rate limiting is enforced via the
 * 'sms' RateLimiter keyed by gateway ID (requests_per_second on the Gateway model).
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

    public function getGateway(): ?Gateway
    {
        if ($this->messageIds !== []) {
            $firstMessage = Message::with('gateway')->find($this->messageIds[0]);
            if ($firstMessage?->gateway) {
                return $firstMessage->gateway;
            }
        }

        return $this->batch->sender?->gateway;
    }

    public function handle(): void
    {
        $batch = $this->batch->fresh(['sender.gateway']);

        if (! $batch) {
            return;
        }

        $gateway = $this->getGateway();

        if (! $gateway) {
            return;
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsBulkSms) {
            return;
        }

        // Atomic idempotency guard: claim all pending messages in this chunk
        // by flipping status from Pending → Sending in a single UPDATE.
        // If another worker already claimed them, affected rows will be 0.
        $ids = $this->messageIds;
        $claimedQuery = $ids !== []
            ? DB::table('messages')->whereIn('id', $ids)->where('status', MessageStatus::Pending->value)
            : DB::table('messages')->where('batch_id', $batch->id)->where('status', MessageStatus::Pending->value);

        $claimed = $claimedQuery->update([
            'status' => MessageStatus::Sending->value,
            'updated_at' => now(),
        ]);

        if ($claimed === 0) {
            return;
        }

        $query = $ids !== []
            ? Message::whereIn('id', $ids)->where('status', MessageStatus::Sending->value)
            : Message::where('batch_id', $batch->id)->where('status', MessageStatus::Sending->value);

        $messages = $query->with(['sender.gateway', 'gateway'])->select(['id', 'to', 'cost', 'is_unicode', 'sender_id', 'gateway_id'])->orderBy('id')->get();

        if ($messages->isEmpty()) {
            return;
        }

        if (! app(SmsSettings::class)->sms_enabled) {
            SmsLogger::error('sms_disabled', [
                'user_id' => $batch->user_id,
                'status' => 'failed',
                'error_message' => 'SMS sending is globally disabled in SMS Settings.',
            ]);

            $this->applyBulkResponse(
                messages: $messages,
                response: new BulkSmsResponse(successful: false, errorMessage: 'SMS sending is globally disabled.'),
                gateway: $gateway,
                requestId: (string) Str::uuid(),
            );
            $this->refundFailedMessages($batch, $messages);

            return;
        }

        $from = $messages->first()->sender?->sender_id ?? $batch->sender?->sender_id;
        $recipients = $messages->map(fn (Message $m): string => (string) $m->to)->all();
        $isUnicode = (bool) $messages->first()->is_unicode;

        // Resolve priority gateways
        $routeResolver = app(SmsRouteResolver::class);
        $gateways = $routeResolver->resolveGateways(
            recipient: $recipients[0] ?? null,
            trafficType: 'promotional',
            sender: $messages->first()->sender ?? $batch->sender,
            preferredGateway: $gateway,
        )->filter(fn (Gateway $g) => $g->supports_bulk);

        // When the sender CLI is a numeric MSISDN, only allow gateways whose
        // bill_msisdn matches. This prevents "Invalid CLI!" failures caused by
        // routing a numeric CLI through a gateway with a mismatched bill_msisdn.
        $gateways = $this->filterGatewaysForNumericCli($gateways, $from);

        if ($gateways->isEmpty()) {
            $gateways = collect([$gateway]);
        }

        $successfulResponse = null;
        $lastError = 'No bulk provider responded successfully.';
        $successfulGateway = $gateway;
        $lastGateway = $gateway;
        $lastRequestId = (string) Str::uuid();

        foreach ($gateways as $attemptIndex => $currentGateway) {
            $driver = Sms::usingGateway($currentGateway)->driver($currentGateway->driver->value);

            if (! $driver instanceof SupportsBulkSms) {
                continue;
            }

            $requestId = (string) Str::uuid();

            try {
                $response = $driver->sendBulk(new BulkSmsRequest(
                    recipients: $recipients,
                    body: $batch->body,
                    from: $from,
                    isUnicode: $isUnicode,
                ));

                if ($response->successful) {
                    $successfulResponse = $response;
                    $successfulGateway = $currentGateway;
                    $lastRequestId = $requestId;

                    SmsLogger::info('bulk_sms_sent', [
                        'user_id' => $batch->user_id,
                        'gateway_id' => $currentGateway->id,
                        'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                        'status' => 'sent',
                        'parts' => count($recipients),
                        'gateway_message_id' => $response->gatewayBatchId,
                        'job_id' => $this->job?->getJobId(),
                        'queue' => $this->queue,
                    ]);

                    break;
                }

                $lastError = $response->errorMessage ?? 'Bulk provider rejected request';
                $lastGateway = $currentGateway;
                $lastRequestId = $requestId;

                SmsLogger::warning('bulk_sms_gateway_failover', [
                    'user_id' => $batch->user_id,
                    'gateway_id' => $currentGateway->id,
                    'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                    'status' => 'failover',
                    'error_message' => "Attempt {$attemptIndex} bulk failed: {$lastError}. Trying next priority provider...",
                    'job_id' => $this->job?->getJobId(),
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $lastGateway = $currentGateway;
                $lastRequestId = $requestId;

                SmsLogger::warning('bulk_sms_gateway_failover_exception', [
                    'user_id' => $batch->user_id,
                    'gateway_id' => $currentGateway->id,
                    'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                    'status' => 'failover',
                    'error_message' => "Attempt {$attemptIndex} bulk exception: {$lastError}. Trying next priority provider...",
                    'job_id' => $this->job?->getJobId(),
                ]);
            }
        }

        $finalResponse = $successfulResponse ?? new BulkSmsResponse(successful: false, errorMessage: $lastError);

        $failedMessages = $this->applyBulkResponse(
            messages: $messages,
            response: $finalResponse,
            gateway: $successfulResponse === null ? $lastGateway : $successfulGateway,
            requestId: $lastRequestId,
            from: $from,
        );

        if (! $finalResponse->successful) {
            SmsLogger::error('bulk_sms_failed_all_gateways', [
                'user_id' => $batch->user_id,
                'status' => 'failed',
                'parts' => count($recipients),
                'error_message' => "All priority bulk gateways failed: {$lastError}",
                'job_id' => $this->job?->getJobId(),
            ]);

            $this->refundFailedMessages($batch, $failedMessages);
        } elseif ($failedMessages->isNotEmpty()) {
            SmsLogger::warning('bulk_sms_recipient_rejected', [
                'user_id' => $batch->user_id,
                'gateway_id' => $successfulGateway->id,
                'gateway_name' => $successfulGateway->name ?? $successfulGateway->driver->value,
                'status' => 'partial_failure',
                'parts' => $failedMessages->count(),
                'error_message' => 'Infozillion rejected one or more recipients in an otherwise accepted bulk request.',
                'job_id' => $this->job?->getJobId(),
            ]);

            $this->refundFailedMessages($batch, $failedMessages);
        }

        // Schedule DLR polling via DB so the command can pick it up even after a Redis flush.
        if ($finalResponse->successful && Sms::usingGateway($successfulGateway)->driver($successfulGateway->driver->value) instanceof SupportsDeliveryStatus) {
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
                'next_poll_at' => now()->addMinutes(1),
            ]);
    }

    /**
     * @return Collection<int, Message>
     */
    private function applyBulkResponse(
        Collection $messages,
        BulkSmsResponse $response,
        Gateway $gateway,
        string $requestId,
        ?string $from = null,
    ): Collection {
        $failedMessages = $this->failedMessages($messages, $response);
        $acceptedMessages = $messages->reject(fn (Message $message): bool => $failedMessages->has($message->id));
        $providerSnapshot = InfozillionMessageSnapshot::fromResponse(
            gateway: $gateway,
            response: $response->raw,
            requestId: $requestId,
            defaultSmsType: 'P',
            from: $from,
        );

        foreach ($failedMessages as $message) {
            $message->update(array_merge([
                'gateway_id' => $gateway->id,
                'status' => MessageStatus::Failed->value,
                'sent_at' => null,
                'failed_at' => Carbon::now(),
                'failed_reason' => $response->failedRecipients[$this->normalizedRecipient($message->to)] ?? $response->errorMessage,
                'gateway_message_id' => null,
                'poll_attempt' => null,
                'next_poll_at' => null,
            ], $providerSnapshot));
        }

        if ($acceptedMessages->isEmpty()) {
            return $failedMessages;
        }

        $this->applyAcceptedBulkResponse($acceptedMessages, $response, $gateway->id, $providerSnapshot);

        return $failedMessages;
    }

    /**
     * @param  array<string, mixed>  $providerSnapshot
     */
    private function applyAcceptedBulkResponse(
        Collection $messages,
        BulkSmsResponse $response,
        ?int $gatewayId = null,
        array $providerSnapshot = [],
    ): void {
        $ids = $messages->pluck('id')->all();
        $now = Carbon::now();

        $commonUpdate = array_merge([
            'status' => $response->successful ? MessageStatus::Sent->value : MessageStatus::Failed->value,
            'sent_at' => $response->successful ? $now : null,
            'failed_at' => $response->successful ? null : $now,
            'failed_reason' => $response->errorMessage,
            'updated_at' => $now,
        ], $providerSnapshot);

        if ($gatewayId !== null) {
            $commonUpdate['gateway_id'] = $gatewayId;
        }

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

    /**
     * @return Collection<int, Message>
     */
    private function failedMessages(Collection $messages, BulkSmsResponse $response): Collection
    {
        if (! $response->successful) {
            return $messages->keyBy('id');
        }

        return $messages
            ->filter(fn (Message $message): bool => array_key_exists(
                $this->normalizedRecipient($message->to),
                $response->failedRecipients,
            ))
            ->keyBy('id');
    }

    private function normalizedRecipient(string $recipient): string
    {
        return ltrim($recipient, '+');
    }

    /**
     * Filters the gateway chain to gateways whose bill_msisdn matches the given
     * numeric CLI. Non-numeric (masking) sender IDs bypass this filter entirely.
     *
     * @param  BaseCollection<int, Gateway>  $gateways
     * @return BaseCollection<int, Gateway>
     */
    private function filterGatewaysForNumericCli(BaseCollection $gateways, ?string $from): BaseCollection
    {
        if ($from === null || $from === '') {
            return $gateways;
        }

        // Normalize to bare digits (strip +, spaces, leading zeros)
        $normalizedCli = $this->normalizeMsisdn($from);

        // If the sender ID is not a recognisable Bangladesh MSISDN (i.e. it is a
        // masking/alpha-numeric name), do not filter — all gateways are valid.
        if ($normalizedCli === null) {
            return $gateways;
        }

        $filtered = $gateways->filter(function (Gateway $gw) use ($normalizedCli): bool {
            $billMsisdn = $this->normalizeMsisdn(
                (string) data_get($gw->credentials, 'bill_msisdn', '')
            );

            return $billMsisdn !== null && $billMsisdn === $normalizedCli;
        })->values();

        // If no gateway in the chain matches, return all gateways so the driver's
        // own invalidNumericCliError() check can produce a clear failure message.
        return $filtered->isNotEmpty() ? $filtered : $gateways;
    }

    /**
     * Normalises a Bangladesh MSISDN to "880XXXXXXXXX" (12 digits, no plus).
     * Returns null for non-numeric or non-Bangladesh values (e.g. masking names).
     */
    private function normalizeMsisdn(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Masking / alphanumeric sender IDs contain letters (e.g. "BRAND_SMS", "MyCompany")
        if (preg_match('/[a-zA-Z]/', $value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        // International format starting with 880 (mobile 13 digits, IPTSP 13-14 digits)
        if (str_starts_with($digits, '880') && strlen($digits) >= 11 && strlen($digits) <= 15) {
            return $digits;
        }

        // Local format starting with 0 (e.g., 017..., 096...)
        if (str_starts_with($digits, '0') && strlen($digits) >= 10 && strlen($digits) <= 13) {
            return '880'.substr($digits, 1);
        }

        // Bare number without 0 or 880 prefix (e.g., 17..., 96...)
        if (strlen($digits) >= 9 && strlen($digits) <= 12) {
            return '880'.$digits;
        }

        return $digits;
    }
}
