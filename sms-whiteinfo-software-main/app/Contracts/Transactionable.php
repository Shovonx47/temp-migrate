<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Transactionable
{
    public function transactions(): MorphMany;
}
