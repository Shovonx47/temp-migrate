<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[Guarded(['id'])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'phone' => E164PhoneNumberCast::class,
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'contact_group');
    }
}
