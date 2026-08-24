<?php

namespace App\Models;

use App\Models\Scopes\OwnedByUser;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[Guarded(['id'])]
#[ScopedBy(OwnedByUser::class)]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'phone' => E164PhoneNumberCast::class.':BD',
            'meta' => 'array',
        ];
    }

    public function setPhoneAttribute(mixed $value): void
    {
        if (is_string($value)) {
            $cleaned = preg_replace('/[^\d+]/', '', $value);
            if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $cleaned, $m)) {
                $this->attributes['phone'] = '+880'.$m[1];

                return;
            }
            $this->attributes['phone'] = $cleaned;

            return;
        }

        $this->attributes['phone'] = $value;
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
