<?php

namespace App\Models;

use App\Enums\MessageBatchStatus;
use App\Jobs\ProcessMessageBatchJob;
use Database\Factories\MessageBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class MessageBatch extends Model
{
    /** @use HasFactory<MessageBatchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source_config' => 'array',
            'status' => MessageBatchStatus::class,
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function retry(): void
    {
        $this->update([
            'status' => MessageBatchStatus::Processing,
            'started_at' => null,
            'completed_at' => null,
            'failed_count' => 0,
            'failed_report_path' => null,
        ]);

        ProcessMessageBatchJob::dispatch(
            $this,
            $this->source_type,
            $this->source_config ?? [],
            $this->body,
            $this->extractBodyVariables(),
        );
    }

    /**
     * @return string[]
     */
    private function extractBodyVariables(): array
    {
        preg_match_all('/{(\w+)}/', (string) $this->body, $matches);

        return array_values(array_unique($matches[1]));
    }
}
