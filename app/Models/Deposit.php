<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Enums\DepositStatus;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded(['id'])]
class Deposit extends Model implements Transactionable
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory, SoftDeletes, HasTransactions;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'status' => DepositStatus::class,
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
