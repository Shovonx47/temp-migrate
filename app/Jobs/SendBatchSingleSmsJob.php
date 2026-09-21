<?php

namespace App\Jobs;

use App\Actions\Billing\CreditBalance;
use App\Enums\MessageStatus;
use App\Models\Batch;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\User;
use App\Services\Logging\SmsLogger;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Routing\SmsRouteResolver;
use App\Settings\SmsSettings;
use App\Support\Facades\Sms;
use App\Support\InfozillionMessageSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sends a single pending message via an individual API request.
 *
 * One job dispatch = one gateway API call. Rate limiting is enforced via the
 * 'sms' RateLimiter keyed by gateway ID (requests_per_second on the Gateway model).
 *
 * Use this job when:
 *   - The batch body has {variable} placeholders, OR
 *   - The resolved driver does not implement SupportsBulkSms
 *
 * The job is idempotent: if the message is no longer pending when it executes
 * (e.g. already processed by a duplicate dispatch), it exits silently.
 */
class SendBatchSingleSmsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    public function __construct(
        public readonly Batch $batch,
        public readonly int $messageId,
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
        $message = Message::with('gateway')->find($this->messageId);

        return $message?->gateway ?? $this->batch->sender?->gateway;
    }

    public function handle(): void
    {
        $batch = $this->batch->fresh(['sender.gateway']);

        if (! $batch) {
            return;
        }

        $message = Message::where('id', $this->messageId)
            ->with(['sender.gateway', 'gateway'])
            ->select(['id', 'to', 'body', 'cost', 'user_id', 'is_unicode', 'sender_id', 'gateway_id'])
            ->first();

        if (! $message) {
            return;
        }

        $gateway = $message->gateway ?? $message->sender?->gateway ?? $batch->sender?->gateway;

        if (! $gateway) {
            return;
        }

        // Atomic idempotency guard: claim this message by flipping status from
        // Pending → Sending in a single UPDATE. If another worker already claimed
        // it (or it's no longer pending), affected rows will be 0 and we exit.
        $claimed = DB::table('messages')
            ->where('id', $this->messageId)
            ->where('status', MessageStatus::Pending->value)
            ->update([
                'status' => MessageStatus::Sending->value,
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return;
        }

        // Check global SMS sending kill-switch
        if (! app(SmsSettings::class)->sms_enabled) {
            SmsLogger::error('sms_disabled', [
                'message_id' => $message->id,
                'user_id' => $message->user_id,
                'phone' => (string) $message->to,
                'status' => 'failed',
                'error_message' => 'SMS sending is globally disabled in SMS Settings.',
            ]);

            $this->failAndRefund($message, now(), 'SMS sending is globally disabled.');

            return;
        }

        $from = $message->sender?->sender_id ?? $batch->sender?->sender_id;
        $now = Carbon::now();

        // Resolve priority gateway chain (Priority 1 -> Priority 2 -> Priority 3)
        $routeResolver = app(SmsRouteResolver::class);
        $gateways = $routeResolver->resolveGateways(
            recipient: (string) $message->to,
            trafficType: 'transactional',
            sender: $message->sender ?? $batch->sender,
            preferredGateway: $gateway,
        );

        // When the sender CLI is a numeric MSISDN, only allow gateways whose
        // bill_msisdn matches. This prevents "Invalid CLI!" failures caused by
        // routing a numeric CLI through a gateway with a mismatched bill_msisdn.
        $gateways = $this->filterGatewaysForNumericCli($gateways, $from);

        if ($gateways->isEmpty()) {
            $gateways = collect([$gateway]);
        }

        $lastError = 'No available gateway responded successfully.';
        $lastGateway = $gateway;
        $lastResponse = [];
        $lastRequestId = (string) Str::uuid();

        foreach ($gateways as $attemptIndex => $currentGateway) {
            $driver = Sms::usingGateway($currentGateway)->driver($currentGateway->driver->value);
            $requestId = (string) Str::uuid();

            try {
                $response = $driver->send(new SmsRequest(
                    to: (string) $message->to,
                    body: $message->body,
                    from: $from,
                    isUnicode: (bool) $message->is_unicode,
                ));

                if ($response->successful) {
                    $providerSnapshot = InfozillionMessageSnapshot::fromResponse(
                        gateway: $currentGateway,
                        response: $response->raw,
                        requestId: $requestId,
                        defaultSmsType: 'T',
                        senderCli: $from,
                    );

                    SmsLogger::info('sms_sent', [
                        'message_id' => $message->id,
                        'user_id' => $message->user_id,
                        'gateway_id' => $currentGateway->id,
                        'phone' => (string) $message->to,
                        'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                        'gateway_message_id' => $response->gatewayMessageId,
                        'status' => 'sent',
                        'job_id' => $this->job?->getJobId(),
                        'queue' => $this->queue,
                    ]);

                    $message->update(array_merge([
                        'gateway_id' => $currentGateway->id,
                        'status' => MessageStatus::Sent->value,
                        'sent_at' => $now,
                        'gateway_message_id' => $response->gatewayMessageId,
                        'failed_at' => null,
                        'failed_reason' => null,
                        'poll_attempt' => $response->gatewayMessageId !== null ? 1 : null,
                        'next_poll_at' => $response->gatewayMessageId !== null ? $now->copy()->addMinutes(1) : null,
                        'updated_at' => $now,
                    ], $providerSnapshot));

                    return;
                }

                $lastError = $response->errorMessage ?? 'Gateway rejected SMS';
                $lastGateway = $currentGateway;
                $lastResponse = $response->raw;
                $lastRequestId = $requestId;

                SmsLogger::warning('sms_gateway_failover', [
                    'message_id' => $message->id,
                    'user_id' => $message->user_id,
                    'gateway_id' => $currentGateway->id,
                    'phone' => (string) $message->to,
                    'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                    'status' => 'failover',
                    'error_message' => "Attempt {$attemptIndex} failed: {$lastError}. Trying next priority provider...",
                    'job_id' => $this->job?->getJobId(),
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $lastGateway = $currentGateway;
                $lastResponse = [];
                $lastRequestId = $requestId;

                SmsLogger::warning('sms_gateway_failover_exception', [
                    'message_id' => $message->id,
                    'user_id' => $message->user_id,
                    'gateway_id' => $currentGateway->id,
                    'phone' => (string) $message->to,
                    'gateway_name' => $currentGateway->name ?? $currentGateway->driver->value,
                    'status' => 'failover',
                    'error_message' => "Attempt {$attemptIndex} exception: {$lastError}. Trying next priority provider...",
                    'job_id' => $this->job?->getJobId(),
                ]);
            }
        }

        // If all priority gateways fail
        SmsLogger::error('sms_send_failed_all_gateways', [
            'message_id' => $message->id,
            'user_id' => $message->user_id,
            'phone' => (string) $message->to,
            'status' => 'failed',
            'error_message' => "All {$gateways->count()} priority gateway(s) failed. Final error: {$lastError}",
            'job_id' => $this->job?->getJobId(),
        ]);

        $this->failAndRefund(
            message: $message,
            now: $now,
            reason: $lastError,
            providerSnapshot: InfozillionMessageSnapshot::fromResponse(
                gateway: $lastGateway,
                response: $lastResponse,
                requestId: $lastRequestId,
                defaultSmsType: 'T',
                senderCli: $from,
            ),
        );
    }

    /**
     * Filters the gateway chain to gateways whose bill_msisdn matches the given
     * numeric CLI. Non-numeric (masking) sender IDs bypass this filter entirely.
     *
     * @param  Collection<int, Gateway>  $gateways
     * @return Collection<int, Gateway>
     */
    private function filterGatewaysForNumericCli(Collection $gateways, ?string $from): Collection
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

        /*
         * IPTSP gateways are NOT filtered by the gateway-level bill_msisdn.
         *
         * For IPTSP, the selected numeric Sender CLI becomes BOTH:
         *   - billMsisdn
         *   - cli
         *
         * The driver builds the final IPTSP request from the selected Sender CLI.
         * Therefore the gateway's configured bill_msisdn must not exclude it here.
         *
         * Non-IPTSP gateways keep the existing bill_msisdn matching behaviour.
         */
        $filtered = $gateways->filter(function (Gateway $gw) use ($normalizedCli): bool {
            $apiType = strtolower(trim((string) data_get($gw->credentials, 'api_type', '')));

            if ($apiType === 'iptsp') {
                return true;
            }

            $billMsisdn = $this->normalizeMsisdn(
                (string) data_get($gw->credentials, 'bill_msisdn', '')
            );

            return $billMsisdn !== null && $billMsisdn === $normalizedCli;
        })->values();

        // If no gateway in the chain matches, keep the original chain.
        return $filtered->isNotEmpty() ? $filtered : $gateways;
    }

    /**
     * Normalises a Bangladesh MSISDN to "880XXXXXXXXX" (12 digits, no plus).
     * Returns null for non-numeric or non-Bangladesh values (e.g. masking names).
     */
    private function normalizeMsisdn(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (preg_match('/^(?:880|0)?(1[3-9]\d{8})$/', $digits, $matches)) {
            return '880'.$matches[1];
        }

        return null;
    }

    /**
     * Marks the message as Failed and refunds its cost to the user's balance in a single transaction.
     *
     * @param  array<string, mixed>  $providerSnapshot
     */
    private function failAndRefund(Message $message, Carbon $now, ?string $reason, array $providerSnapshot = []): void
    {
        DB::transaction(function () use ($message, $now, $reason, $providerSnapshot): void {
            $message->update(array_merge([
                'status' => MessageStatus::Failed->value,
                'failed_at' => $now,
                'failed_reason' => $reason,
                'sent_at' => null,
                'updated_at' => $now,
            ], $providerSnapshot));

            if (($message->cost ?? 0) > 0) {
                /** @var User $user */
                $user = User::findOrFail($message->user_id);

                app(CreditBalance::class)->handle(
                    user: $user,
                    amount: $message->cost,
                    transactionable: $message,
                    description: 'Refund: SMS failed to send',
                );
            }
        });
    }
}
