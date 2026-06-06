<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;

class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Batch $batch): bool
    {
        return $batch->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Batch $batch): bool
    {
        return $batch->user_id === $user->id;
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $batch->user_id === $user->id;
    }

    public function restore(User $user, Batch $batch): bool
    {
        return $batch->user_id === $user->id;
    }

    public function forceDelete(User $user, Batch $batch): bool
    {
        return $batch->user_id === $user->id;
    }
}
