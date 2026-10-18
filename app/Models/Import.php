<?php

namespace App\Models;

use App\Models\Scopes\OwnedByUser;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(OwnedByUser::class)]
class Import extends Model
{
    //
}
