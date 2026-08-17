<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->type === UserType::Superman
            || $user->type === UserType::Sp;
    }

    public function view(User $user, User $model): bool
    {
        if ($user->type === UserType::Superman || $user->id === $model->id) {
            return true;
        }

        return $this->spOwnsCustomer($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->type === UserType::Superman
            || $user->type === UserType::Sp;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->type === UserType::Superman || $user->id === $model->id) {
            return true;
        }

        return $this->spOwnsCustomer($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->type === UserType::Superman) {
            return true;
        }

        return $this->spOwnsCustomer($user, $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->type === UserType::Superman;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->type === UserType::Superman;
    }

    private function spOwnsCustomer(User $actor, User $model): bool
    {
        return $actor->type === UserType::Sp
            && $model->type === UserType::Customer
            && $model->sp_id === $actor->id;
    }
}
