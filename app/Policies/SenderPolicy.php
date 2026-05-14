<?php

namespace App\Policies;

use App\Models\Sender;
use App\Models\User;

class SenderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sender $sender): bool
    {
        return $sender->users()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Sender $sender): bool
    {
        return false;
    }

    public function delete(User $user, Sender $sender): bool
    {
        return false;
    }

    public function restore(User $user, Sender $sender): bool
    {
        return false;
    }

    public function forceDelete(User $user, Sender $sender): bool
    {
        return false;
    }
}
