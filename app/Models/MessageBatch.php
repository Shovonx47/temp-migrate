<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Enums\MessageBatchSource;
use App\Enums\MessageBatchStatus;
use App\Enums\SmsApproval;
use App\Jobs\ProcessMessageBatchJob;
use Database\Factories\MessageBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class MessageBatch extends Model implements Transactionable
{
    /** @use HasFactory<MessageBatchFactory> */
    use HasFactory, HasTransactions;

    protected function casts(): array
    {
        return [
            'source_config' => 'array',
            'source_type' => MessageBatchSource::class,
            'status' => MessageBatchStatus::class,
            'approval' => SmsApproval::class,
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAwaitingApproval(): bool
    {
        return $this->status === MessageBatchStatus::Pending;
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    protected function processedCount(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->valid_count + (int) $this->invalid_count);
    }

    protected function progressPercent(): Attribute
    {
        return Attribute::get(function (): int {
            $total = (int) $this->total_recipients;

            if ($total === 0) {
                return 0;
            }

            return (int) floor($this->processed_count / $total * 100);
        });
    }

    protected function progressLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $total = (int) $this->total_recipients;
            $processed = $this->processed_count;

            if ($total === 0) {
                return $processed > 0 ? (string) $processed : '—';
            }

            return "{$this->progress_percent}% ({$processed} / {$total})";
        });
    }

    protected function progressRemaining(): Attribute
    {
        return Attribute::get(function (): int {
            $total = (int) $this->total_recipients;
            $processed = $this->processed_count;

            return max($total - $processed, 0);
        });
    }

    /**
     * Whether the batch body contains any {variable} placeholders.
     * Batches without variables send the same body to every recipient
     * and are eligible for bulk gateway delivery.
     */
    public function hasBodyVariables(): bool
    {
        return $this->extractBodyVariables() !== [];
    }

    public function retry(): void
    {
        if ($this->status !== MessageBatchStatus::Failed) {
            return;
        }

        $this->update([
            'status' => MessageBatchStatus::Processing,
            'completed_at' => null,
        ]);

        ProcessMessageBatchJob::dispatch(
            $this,
            $this->source_type instanceof MessageBatchSource ? $this->source_type->value : (string) $this->source_type,
            $this->source_config ?? [],
            $this->body,
            $this->extractBodyVariables(),
        )->onQueue('message-batches');
    }

    /**
     * @return string[]
     */
    public function extractBodyVariables(): array
    {
        preg_match_all('/{(\w+)}/', (string) $this->body, $matches);

        return array_values(array_unique($matches[1]));
    }
}
