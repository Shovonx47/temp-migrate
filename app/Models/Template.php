<?php

namespace App\Models;

use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded(['id'])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }
}
