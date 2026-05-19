<?php

namespace App\Policies;

use App\Models\AccessToken;
use App\Models\User;

class AccessTokenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->api_access;
    }

    public function view(User $user, AccessToken $accessToken): bool
    {
        return $accessToken->tokenable_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->api_access;
    }

    public function update(User $user, AccessToken $accessToken): bool
    {
        return $accessToken->tokenable_id === $user->id;
    }

    public function delete(User $user, AccessToken $accessToken): bool
    {
        return $accessToken->tokenable_id === $user->id;
    }

    public function restore(User $user, AccessToken $accessToken): bool
    {
        return $accessToken->tokenable_id === $user->id;
    }

    public function forceDelete(User $user, AccessToken $accessToken): bool
    {
        return $accessToken->tokenable_id === $user->id;
    }
}
