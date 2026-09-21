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

        // Only parent S P can delete their own customers or employees
        return $user->type === UserType::Sp
            && ! $user->sp_id
            && $model->sp_id === $user->id;
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
        if ($actor->type !== UserType::Sp) {
            return false;
        }

        // Parent S P owns their customers and employees
        if (! $actor->sp_id) {
            return $model->sp_id === $actor->id;
        }

        // S P Employee can access the parent S P's customers
        return $model->type === UserType::Customer && $model->sp_id === $actor->sp_id;
    }
}
