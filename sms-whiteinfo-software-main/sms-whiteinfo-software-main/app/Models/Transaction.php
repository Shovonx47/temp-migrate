<?php

namespace App\Models;

use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
use App\Models\Scopes\OwnedByUser;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Guarded(['id'])]
#[ScopedBy(OwnedByUser::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'direction' => TransactionDirection::class,
            'balance_before' => 'float',
            'balance_after' => 'float',
            'transactionable_type' => TransactionableType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
