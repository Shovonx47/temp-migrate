<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'to' => E164PhoneNumberCast::class,
            'status' => MessageStatus::class,
            'cost' => 'float',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }
}
