<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\BatchSource;
use App\Enums\BatchStatus;
use App\Enums\SmsApproval;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchJob;
use App\Models\Batch;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use App\Support\MessageMetrics;
use App\Support\OperatorRateResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MessageApiController extends Controller
{
    /**
     * Display a paginated list of SMS delivery logs for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Message::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->with(['sender:id,sender_id']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('to', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('msg_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sort = (string) $request->input('sort', 'id');
        $dir = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'to', 'status', 'cost', 'created_at', 'sent_at'];
        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->latest('id');
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Message $m): array => [
            'id' => $m->id,
            'msg_id' => $m->msg_id,
            'sender_id' => $m->sender?->sender_id ?? '—',
            'to' => (string) $m->to,
            'body' => (string) $m->body,
            'status' => $m->status instanceof \BackedEnum ? $m->status->value : (string) $m->status,
            'cost' => (string) number_format((float) $m->cost, 4),
            'parts' => (int) ($m->parts ?? 1),
            'created_at' => $m->created_at?->toISOString(),
            'sent_at' => $m->sent_at?->toISOString(),
            'delivered_at' => $m->delivered_at?->toISOString(),
            'error_message' => $m->error_message,
        ]);

        return new JsonResponse([
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Send an instant quick message.
     */
    public function send(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => ['required', 'integer'],
            'phones' => ['required'],
            'body' => ['required', 'string'],
        ]);

        $sender = $user->senders()->where('senders.id', $validated['sender_id'])->first();
        if (! $sender) {
            return new JsonResponse(['message' => 'Selected Sender ID is not available.'], 422);
        }

        $phones = $this->normalizeNumbers($validated['phones']);
        if ($phones === []) {
            return new JsonResponse(['message' => 'At least one valid phone number is required.'], 422);
        }

        $body = trim((string) $validated['body']);
        if (! $this->canAfford($user, $body, count($phones), $sender)) {
            return new JsonResponse([
                'message' => 'Insufficient balance for this message.',
                'balance' => (float) $user->balance,
            ], 402);
        }

        $recipients = array_map(fn (string $phone): array => ['phone' => $phone, 'values' => []], $phones);
        $requiresApproval = $user->requiresSmsApproval();
        $clientIp = $request->ip();

        $batch = DB::transaction(fn (): Batch => Batch::create([
            'user_id' => $user->id,
            'sender_id' => $sender->id,
            'body' => $body,
            'source_type' => 'manual',
            'source_ip' => $clientIp,
            'source_config' => [
                'recipients' => $recipients,
                'ip' => $clientIp,
            ],
            'status' => $requiresApproval ? BatchStatus::Pending : BatchStatus::Processing,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]));

        if (! $requiresApproval) {
            try {
                ProcessBatchJob::dispatchSync(
                    $batch,
                    'manual',
                    $batch->source_config,
                    $body,
                    [],
                    syncSending: true,
                );
            } catch (Throwable) {
                // Background job handles failure and marks batch accordingly
            }
        }

        return new JsonResponse([
            'data' => [
                'id' => $batch->id,
                'status' => $batch->fresh()?->status?->value ?? 'processing',
                'recipients' => count($recipients),
            ],
        ], 201);
    }

    /**
     * Schedule a quick message for a future date/time.
     */
    public function schedule(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => ['required', 'integer'],
            'phones' => ['required'],
            'body' => ['required', 'string'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_hour' => ['required', 'string'],
            'scheduled_minute' => ['required', 'string'],
            'scheduled_period' => ['required', 'in:AM,PM,am,pm'],
        ]);

        $sender = $user->senders()->where('senders.id', $validated['sender_id'])->first();
        if (! $sender) {
            return new JsonResponse(['message' => 'Selected Sender ID is not available.'], 422);
        }

        $phones = $this->normalizeNumbers($validated['phones']);
        if ($phones === []) {
            return new JsonResponse(['message' => 'At least one valid phone number is required.'], 422);
        }

        $hour = str_pad((string) $validated['scheduled_hour'], 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) $validated['scheduled_minute'], 2, '0', STR_PAD_LEFT);
        $period = strtoupper((string) $validated['scheduled_period']);
        $rawDate = Carbon::parse((string) $validated['scheduled_date'])->format('Y-m-d');

        $scheduledAt = Carbon::createFromFormat('Y-m-d h:i A', "{$rawDate} {$hour}:{$minute} {$period}", config('app.timezone'));
        if ($scheduledAt->lt(now()->subMinute())) {
            return new JsonResponse(['message' => 'The selected schedule time has already passed.'], 422);
        }

        $body = trim((string) $validated['body']);
        if (! $this->canAfford($user, $body, count($phones), $sender)) {
            return new JsonResponse(['message' => 'Insufficient balance for this scheduled message.'], 402);
        }

        $recipients = array_map(fn (string $phone): array => ['phone' => $phone, 'values' => []], $phones);
        $requiresApproval = $user->requiresSmsApproval();
        $clientIp = $request->ip();

        $batch = DB::transaction(fn (): Batch => Batch::create([
            'user_id' => $user->id,
            'sender_id' => $sender->id,
            'body' => $body,
            'source_type' => 'manual',
            'source_ip' => $clientIp,
            'source_config' => [
                'recipients' => $recipients,
                'ip' => $clientIp,
            ],
            'status' => BatchStatus::Scheduled,
            'scheduled_at' => $scheduledAt,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]));

        return new JsonResponse([
            'data' => [
                'id' => $batch->id,
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt->toISOString(),
            ],
        ], 201);
    }

    /**
     * Send or schedule a bulk campaign batch.
     */
    public function bulkSend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => ['required', 'integer'],
            'body' => ['required', 'string'],
            'source' => ['required', 'in:manual,csv,upload,group'],
            'file_rows' => ['nullable', 'array'],
            'phone_column' => ['nullable', 'string'],
            'phones' => ['nullable'],
            'groups' => ['nullable', 'array'],
            'body_variables' => ['nullable', 'array'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_hour' => ['nullable', 'string'],
            'scheduled_minute' => ['nullable', 'string'],
            'scheduled_period' => ['nullable', 'in:AM,PM,am,pm'],
        ]);

        $sender = $user->senders()->where('senders.id', $validated['sender_id'])->first();
        if (! $sender) {
            return new JsonResponse(['message' => 'Selected Sender ID is not available.'], 422);
        }

        $source = (string) $validated['source'];
        $recipients = [];

        if ($source === 'manual') {
            $phones = $this->normalizeNumbers($validated['phones'] ?? []);
            $recipients = array_map(fn (string $p): array => ['phone' => $p, 'values' => []], $phones);
        } elseif ($source === 'csv' || $source === 'upload') {
            $phoneCol = (string) ($validated['phone_column'] ?? 'phone');
            $rows = (array) ($validated['file_rows'] ?? []);
            foreach ($rows as $row) {
                if (is_array($row) && ! empty($row[$phoneCol])) {
                    $recipients[] = [
                        'phone' => trim((string) $row[$phoneCol]),
                        'values' => $row,
                    ];
                }
            }
        } elseif ($source === 'group') {
            $groupIds = (array) ($validated['groups'] ?? []);
            $contacts = $user->contacts()->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))->get();
            foreach ($contacts as $contact) {
                $recipients[] = [
                    'phone' => (string) $contact->phone,
                    'values' => ['name' => (string) $contact->name],
                ];
            }
        }

        if ($recipients === []) {
            return new JsonResponse(['message' => 'No valid recipients found for this bulk campaign.'], 422);
        }

        $body = trim((string) $validated['body']);
        if (! $this->canAfford($user, $body, count($recipients), $sender)) {
            return new JsonResponse(['message' => 'Insufficient balance for this bulk campaign.'], 402);
        }

        // Check if scheduled
        $scheduledAt = null;
        if (! empty($validated['scheduled_date']) && ! empty($validated['scheduled_hour'])) {
            $hour = str_pad((string) $validated['scheduled_hour'], 2, '0', STR_PAD_LEFT);
            $minute = str_pad((string) ($validated['scheduled_minute'] ?? '00'), 2, '0', STR_PAD_LEFT);
            $period = strtoupper((string) ($validated['scheduled_period'] ?? 'AM'));
            $rawDate = Carbon::parse((string) $validated['scheduled_date'])->format('Y-m-d');
            $scheduledAt = Carbon::createFromFormat('Y-m-d h:i A', "{$rawDate} {$hour}:{$minute} {$period}", config('app.timezone'));
        }

        $requiresApproval = $user->requiresSmsApproval();
        $clientIp = $request->ip();

        $batch = DB::transaction(fn (): Batch => Batch::create([
            'user_id' => $user->id,
            'sender_id' => $sender->id,
            'body' => $body,
            'source_type' => $source,
            'source_ip' => $clientIp,
            'source_config' => [
                'recipients' => $recipients,
                'ip' => $clientIp,
            ],
            'status' => $scheduledAt ? BatchStatus::Scheduled : ($requiresApproval ? BatchStatus::Pending : BatchStatus::Processing),
            'scheduled_at' => $scheduledAt,
            'approval' => $requiresApproval ? SmsApproval::Pending : null,
        ]));

        if (! $scheduledAt && ! $requiresApproval) {
            $bodyVariables = (array) ($validated['body_variables'] ?? []);
            if (count($recipients) <= 500) {
                try {
                    ProcessBatchJob::dispatchSync(
                        $batch,
                        $source,
                        $batch->source_config,
                        $body,
                        $bodyVariables,
                        syncSending: true,
                    );
                } catch (Throwable) {
                    // Handled inside job
                }
            } else {
                ProcessBatchJob::dispatch(
                    $batch,
                    $source,
                    $batch->source_config,
                    $body,
                    $bodyVariables,
                );
            }
        }

        return new JsonResponse([
            'data' => [
                'id' => $batch->id,
                'status' => $batch->fresh()?->status?->value ?? ($scheduledAt ? 'scheduled' : 'processing'),
                'recipients' => count($recipients),
            ],
        ], 201);
    }

    /**
     * Normalize phone numbers from array or string.
     *
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
