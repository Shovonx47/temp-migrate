<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MessageBatchStatus;
use App\Enums\SmsApproval;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMessageBatchJob;
use App\Models\MessageBatch;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => [
                'required',
                'integer',
                Rule::exists('sender_user', 'sender_id')->where('user_id', $user->id),
            ],
            'numbers' => ['required'],
            'body' => ['required', 'string'],
        ]);

        $numbers = $this->normalizeNumbers($request->input('numbers'));

        if ($numbers === []) {
            return new JsonResponse(['message' => 'At least one phone number is required.'], 422);
        }

        $recipients = array_map(
            fn (string $number): array => ['phone' => $number, 'values' => []],
            $numbers,
        );

        $body = (string) $validated['body'];
        $senderId = (int) $validated['sender_id'];

        if (! $this->canAfford($user, $body, count($recipients), $senderId)) {
            return new JsonResponse([
                'message' => 'Insufficient balance for this batch.',
                'balance' => (float) $user->balance,
            ], 402);
        }

        $requiresApproval = $user->requiresSmsApproval();

        $batch = DB::transaction(fn (): MessageBatch => MessageBatch::create([
            'user_id' => $user->id,
            'sender_id' => $senderId,
            'body' => $body,
            'source_type' => 'api',
            'source_config' => ['recipients' => $recipients],
            'status' => $requiresApproval ? MessageBatchStatus::Pending : MessageBatchStatus::Processing,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]));

        if (! $requiresApproval) {
            ProcessMessageBatchJob::dispatch(
                $batch,
                'api',
                $batch->source_config,
                $body,
                [],
            )->onQueue('message-batches')->afterCommit();
        }

        return new JsonResponse([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'approval' => $batch->approval,
            'recipients' => count($recipients),
        ], 202);
    }

    public function showBatch(Request $request, int $batch): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = MessageBatch::query()
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
     * @param  mixed  $raw
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

    private function canAfford(User $user, string $body, int $recipientCount, int $senderId): bool
    {
        if ($recipientCount === 0) {
            return false;
        }

        $sender = Sender::find($senderId);

        if (! $sender || $sender->type === null) {
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
