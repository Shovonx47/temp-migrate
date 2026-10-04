<?php

namespace App\Models;

use App\Enums\SmsLogLevel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class SmsLog extends Model
{
    protected function casts(): array
    {
        return [
            'level' => SmsLogLevel::class,
            'cost' => 'float',
            'parts' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    /**
     * Get a terminal-formatted log line for live display.
     */
    public function toTerminalLine(): string
    {
        $ts = $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        $level = strtoupper($this->level->value ?? 'INFO');
        $event = strtoupper($this->event);
        $phone = $this->phone ? " TO={$this->phone}" : '';
        $gw = $this->gateway_name ? " GW={$this->gateway_name}" : '';
        $status = $this->status ? " STATUS={$this->status}" : '';
        $err = $this->error_message ? " ERR=\"{$this->error_message}\"" : '';

        return "[{$ts}] [{$level}] {$event}{$phone}{$gw}{$status}{$err}";
    }
}
