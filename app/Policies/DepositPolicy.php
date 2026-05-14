<?php

namespace App\Policies;

use App\Models\Deposit;
use App\Models\User;

class DepositPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deposit $deposit): bool
    {
        return $deposit->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Deposit $deposit): bool
    {
        return false;
    }

    public function delete(User $user, Deposit $deposit): bool
    {
        return false;
    }

    public function restore(User $user, Deposit $deposit): bool
    {
        return $deposit->user_id === $user->id;
    }

    public function forceDelete(User $user, Deposit $deposit): bool
    {
        return false;
    }
}
