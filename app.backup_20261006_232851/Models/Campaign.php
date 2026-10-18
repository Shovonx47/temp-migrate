<?php

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Contracts\Transactionable;
use App\Enums\CampaignStatus;
use App\Models\Scopes\OwnedByUser;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded(['id'])]
#[ScopedBy(OwnedByUser::class)]
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'campaign_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignContact::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'campaign_contact')
            ->using(CampaignContact::class)
            ->withPivot(['id', 'message_id', 'phone', 'status', 'error', 'cost', 'sent_at'])
            ->withTimestamps();
    }
}
