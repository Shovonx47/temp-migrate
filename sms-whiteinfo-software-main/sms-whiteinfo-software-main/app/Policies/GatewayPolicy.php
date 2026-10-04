<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Gateway;
use App\Models\User;

class GatewayPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gateway $gateway): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->type === UserType::Superman;
    }

    public function update(User $user, Gateway $gateway): bool
    {
        return $user->type === UserType::Superman;
    }

    public function delete(User $user, Gateway $gateway): bool
    {
        return $user->type === UserType::Superman;
    }

    public function restore(User $user, Gateway $gateway): bool
    {
        return $user->type === UserType::Superman;
    }

    public function forceDelete(User $user, Gateway $gateway): bool
    {
        return $user->type === UserType::Superman;
    }
}
