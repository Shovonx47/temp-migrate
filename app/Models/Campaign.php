<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model implements Transactionable
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasTransactions, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'campaign_id');
    }
}
