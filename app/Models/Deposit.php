<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory, SoftDeletes;

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
