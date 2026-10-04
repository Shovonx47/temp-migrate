<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function delete(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function restore(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }
}
