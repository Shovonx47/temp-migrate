<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BatchSource;
use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

#[Group('Messages')]
class MessageController extends Controller
{
    #[Endpoint('Send an SMS batch', <<<'DESC'
        Queues an SMS for delivery. Accepts both `GET` and `POST` — URL-only clients can pass everything as query parameters.

        `numbers` may be either an array (`numbers[]=01700000000&numbers[]=01800000000`) or a single string with comma, semicolon, whitespace, or newline separators (`numbers=01700000000,01800000000`).

        If your account has `sms_auto_approved` disabled the batch is created with `approval=pending` and will not dispatch until an admin approves it.
        DESC)]
    #[QueryParam('sender_id', 'string', 'The sender label (e.g. `FROLAX`, `DDBL`, `8809...`) — not the internal numeric ID. List allowed labels via `GET /senders`.', required: true, example: 'FROLAX')]
    #[QueryParam('numbers', 'string', 'One or more E.164 phone numbers. Array or delimited string.', required: true, example: '01700000000,01800000000')]
    #[QueryParam('body', 'string', 'Message body. Counts against per-part pricing.', required: true, example: 'Hello from Sendora!')]
    #[BodyParam('sender_id', 'string', 'The sender label, e.g. `FROLAX`.', required: true, example: 'FROLAX')]
    #[BodyParam('numbers', 'string', 'One or more E.164 phone numbers. Array or delimited string.', required: true, example: '01700000000,01800000000')]
    #[BodyParam('body', 'string', 'Message body.', required: true, example: 'Hello from Sendora!')]
    #[Response(content: [
        'batch_id' => 42,
        'status' => 'processing',
        'approval' => null,
        'recipients' => 2,
    ], status: 202, description: 'Batch accepted')]
    #[Response(content: [
        'message' => 'Insufficient balance for this batch.',
        'balance' => 5.4,
    ], status: 402, description: 'Insufficient balance')]
    #[Response(content: [
        'message' => 'Sender "FROLAX" is not available for this account.',
    ], status: 422, description: 'Sender not allowed')]
    public function send(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => ['required', 'string'],
            'numbers' => ['required'],
            'body' => ['required', 'string'],
        ]);

        $senderLabel = (string) $validated['sender_id'];

        $sender = $user->senders()
            ->where('senders.sender_id', $senderLabel)
            ->first(['senders.id', 'senders.type']);

        if (! $sender) {
            return new JsonResponse([
                'message' => 'Sender "'.$senderLabel.'" is not available for this account.',
            ], 422);
        }

        $numbers = $this->normalizeNumbers($request->input('numbers'));

        if ($numbers === []) {
            return new JsonResponse(['message' => 'At least one phone number is required.'], 422);
        }

        $recipients = array_map(
            fn (string $number): array => ['phone' => $number, 'values' => []],
            $numbers,
        );

        $body = (string) $validated['body'];

        if (! $this->canAfford($user, $body, count($recipients), $sender)) {
            return new JsonResponse([
                'message' => 'Insufficient balance for this batch.',
                'balance' => (float) $user->balance,
            ], 402);
        }

        $requiresApproval = $user->requiresSmsApproval();

        $batch = DB::transaction(fn (): Batch => Batch::create([
            'user_id' => $user->id,
            'sender_id' => $sender->id,
            'body' => $body,
            'source_type' => BatchSource::API,
            'source_config' => ['recipients' => $recipients],
            'status' => $requiresApproval ? BatchStatus::Pending : BatchStatus::Processing,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]));

        if (! $requiresApproval) {
            ProcessBatchJob::dispatch(
                $batch,
                BatchSource::API->value,
                $batch->source_config,
                $body,
                [],
            )->onQueue('batches')->afterCommit();
        }

        return new JsonResponse([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'approval' => $batch->approval,
            'recipients' => count($recipients),
        ], 202);
    }

    #[Endpoint('Show a message batch')]
    #[UrlParam('batch', 'integer', 'The batch ID returned by `POST /messages`.', required: true, example: 42)]
    #[Response(content: [
        'id' => 42,
        'sender_id' => 12,
        'status' => 'completed',
        'approval' => null,
        'created_at' => '2026-05-19T10:30:00+00:00',
    ], status: 200)]
    #[Response(content: ['message' => 'Batch not found.'], status: 404)]
    public function showBatch(Request $request, int $batch): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = Batch::query()
            ->where('user_id', $user->id)
            ->find($batch);

        if (! $record) {
            return new JsonResponse(['message' => 'Batch not found.'], 404);
        }

        return new JsonResponse([
            'id' => $record->id,
            'sender_id' => $record->sender_id,
            'status' => $record->status,
            'approval' => $record->approval,
            'created_at' => $record->created_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeNumbers(mixed $raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw)) {
            $items = preg_split('/[\s,;]+/', $raw) ?: [];
        } else {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($value): string => trim((string) $value),
            $items,
        )));
    }

    private function canAfford(User $user, string $body, int $recipientCount, Sender $sender): bool
    {
        if ($recipientCount === 0) {
            return false;
        }

        if ($sender->type === null) {
            return true;
        }

        $rates = OperatorRateResolver::forUser($user)->ratesForUser($sender->type->isMasking());

        if ($rates === []) {
            return true;
        }

        $estimated = round(max($rates) * MessageMetrics::for($body)->parts * $recipientCount, 4);

        return (float) $user->balance >= $estimated;
    }
}
