<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->type === UserType::Superman;
    }

    public function view(User $user, User $model): bool
    {
        return $user->type === UserType::Superman || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->type === UserType::Superman;
    }

    public function update(User $user, User $model): bool
    {
        return $user->type === UserType::Superman || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->type === UserType::Superman;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->type === UserType::Superman;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->type === UserType::Superman;
    }
}
