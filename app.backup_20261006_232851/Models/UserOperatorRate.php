<?php

namespace App\Models;

use App\Models\Scopes\OwnedByUser;
use Database\Factories\UserOperatorRateFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
#[ScopedBy(OwnedByUser::class)]
class UserOperatorRate extends Model
{
    /** @use HasFactory<UserOperatorRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'masking_price' => 'float',
            'non_masking_price' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }
}
