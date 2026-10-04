<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Country;
use App\Models\User;

class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Country $country): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->type === UserType::Superman;
    }

    public function update(User $user, Country $country): bool
    {
        return $user->type === UserType::Superman;
    }

    public function delete(User $user, Country $country): bool
    {
        return $user->type === UserType::Superman;
    }

    public function restore(User $user, Country $country): bool
    {
        return $user->type === UserType::Superman;
    }

    public function forceDelete(User $user, Country $country): bool
    {
        return $user->type === UserType::Superman;
    }
}
