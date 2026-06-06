<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserOperatorRate;

class UserOperatorRatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserOperatorRate $userOperatorRate): bool
    {
        return $userOperatorRate->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserOperatorRate $userOperatorRate): bool
    {
        return $userOperatorRate->user_id === $user->id;
    }

    public function delete(User $user, UserOperatorRate $userOperatorRate): bool
    {
        return $userOperatorRate->user_id === $user->id;
    }

    public function restore(User $user, UserOperatorRate $userOperatorRate): bool
    {
        return $userOperatorRate->user_id === $user->id;
    }

    public function forceDelete(User $user, UserOperatorRate $userOperatorRate): bool
    {
        return $userOperatorRate->user_id === $user->id;
    }
}
