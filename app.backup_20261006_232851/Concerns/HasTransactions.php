<?php

namespace App\Concerns;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransactions
{
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}
