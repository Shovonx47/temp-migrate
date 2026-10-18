<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Enums\DepositPaymentMethod;
use App\Enums\DepositStatus;
use App\Models\Scopes\OwnedByUser;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded(['id'])]
#[ScopedBy(OwnedByUser::class)]
class Deposit extends Model implements Transactionable
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory, HasTransactions, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'payment_method' => DepositPaymentMethod::class,
            'status' => DepositStatus::class,
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depositedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposited_by_id');
    }
}
