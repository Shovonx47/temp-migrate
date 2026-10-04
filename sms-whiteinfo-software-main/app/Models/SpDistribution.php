<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Models\Scopes\ScopedBySp;
use Database\Factories\SpDistributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sp_id',
    'customer_id',
    'amount',
    'transaction_id',
    'note',
    'distributed_by',
    'created_at',
])]
#[ScopedBy(ScopedBySp::class)]
class SpDistribution extends Model implements Transactionable
{
    /** @use HasFactory<SpDistributionFactory> */
    use HasFactory, HasTransactions;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function sp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sp_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function debitTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function distributedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
