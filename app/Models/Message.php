<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Enums\MessageSource;
use App\Enums\MessageStatus;
use App\Models\Scopes\OwnedByUser;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[Guarded(['id'])]
#[ScopedBy(OwnedByUser::class)]
class Message extends Model implements Transactionable
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasTransactions;

    protected function casts(): array
    {
        return [
            'to' => E164PhoneNumberCast::class,
            'status' => MessageStatus::class,
            'source' => MessageSource::class,
            'cost' => 'float',
            'primary_dipping_cost' => 'float',
            'primary_dipping_rate' => 'float',
            'third_party_dipping_cost' => 'float',
            'third_party_dipping_rate' => 'float',
            'was_third_party_dipped' => 'boolean',
            'operator_cost' => 'float',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'next_poll_at' => 'datetime',
            'is_unicode' => 'boolean',
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

    public function messageBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public function tariffRate(): BelongsTo
    {
        return $this->belongsTo(TariffRate::class);
    }

    public function dippingCharge(): BelongsTo
    {
        return $this->belongsTo(GatewayDippingCharge::class);
    }
}
