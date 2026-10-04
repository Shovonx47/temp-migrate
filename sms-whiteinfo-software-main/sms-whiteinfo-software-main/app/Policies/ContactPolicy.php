<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }
}
