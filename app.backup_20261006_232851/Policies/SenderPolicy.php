<?php

namespace App\Policies;

use App\Enums\UserType;
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
        if ($user->type === UserType::Superman) {
            return true;
        }

        return $sender->requested_by === $user->id
            || $sender->users()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->type === UserType::Superman
            || $user->type === UserType::Sp;
    }

    public function update(User $user, Sender $sender): bool
    {
        return $user->type === UserType::Superman;
    }

    public function delete(User $user, Sender $sender): bool
    {
        return $user->type === UserType::Superman;
    }

    public function restore(User $user, Sender $sender): bool
    {
        return $user->type === UserType::Superman;
    }

    public function forceDelete(User $user, Sender $sender): bool
    {
        return $user->type === UserType::Superman;
    }
}
