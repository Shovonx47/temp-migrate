<?php

namespace App\Models;

use App\Enums\SenderType;
use Database\Factories\SenderFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded(['id'])]
class Sender extends Model
{
    /** @use HasFactory<SenderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => SenderType::class,
        ];
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sender_user');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'sender_id');
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    #[Scope]
    protected function accessible(Builder $query): Builder
    {
        return $query->where('is_enabled', true)
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            });
    }
}
